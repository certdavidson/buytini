/**
 * SEO Core — Admin JS
 * @copyright Copyright (c) 2026 oc-kit.com. All rights reserved.
 */
document.addEventListener('DOMContentLoaded', function () {

  function buildPagerHtml(page, pages, attr) {
    if (pages <= 1) return '';
    var win = 2, shown = {}, items = [], prev = 0;
    [1, pages].forEach(function(p){ shown[p] = true; });
    for (var p = Math.max(1, page - win); p <= Math.min(pages, page + win); p++) { shown[p] = true; }
    Object.keys(shown).map(Number).sort(function(a,b){ return a - b; }).forEach(function(p) {
      if (prev && p - prev > 1) items.push('<li class="disabled"><span>…</span></li>');
      if (p === page) {
        items.push('<li class="active"><span>' + p + '</span></li>');
      } else {
        items.push('<li><a ' + attr + '="' + p + '">' + p + '</a></li>');
      }
      prev = p;
    });
    return '<ul class="pagination">' + items.join('') + '</ul>';
  }
  window.seoCoreFlowBuildPager = buildPagerHtml;

  function codeBadgeClass(code) {
    code = parseInt(code, 10);
    if (code === 301 || code === 308) return 'success';
    if (code === 410)                 return 'error';
    return 'warning';
  }

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
    });
  }
  window.okEsc = esc;

(function () {
  'use strict';

  var cfg = window.seoCoreConfig || {};
  var i18n = window.seoCoreI18n || {};

  function okPost(url, data, cb) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', url, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function () {
      try { cb(null, JSON.parse(xhr.responseText)); }
      catch (e) { cb(e, null); }
    };
    xhr.onerror = function () { cb(new Error('Network error'), null); };
    xhr.send(new URLSearchParams(data).toString());
  }

  function okGet(url, cb) {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    xhr.onload = function () {
      try { cb(null, JSON.parse(xhr.responseText)); }
      catch (e) { cb(e, null); }
    };
    xhr.onerror = function () { cb(new Error('Network error'), null); };
    xhr.send();
  }

  // ── Settings page ──────────────────────────────────────────────────────────
  function doSaveSettings(btn) {
    var data = {};
    document.querySelectorAll('[name^="module_oc_kit_seo_core_"]').forEach(function (el) {
      if (el.type === 'checkbox') {
        data[el.name] = el.checked ? el.value : '0';
      } else if (el.type === 'radio') {
        if (el.checked) data[el.name] = el.value;
      } else {
        data[el.name] = el.value;
      }
    });
    data['user_token'] = cfg.userToken;

    if (btn) btn.disabled = true;
    okPost(cfg.saveUrl, data, function (err, res) {
      if (btn) btn.disabled = false;
      if (err || !res) { window.okNotify && okNotify(i18n.error || 'Error', 'error'); return; }
      if (res.success) { window.okNotify && okNotify(res.message || i18n.saved || 'Saved', 'success'); }
      else { window.okNotify && okNotify(res.message || i18n.error || 'Error', 'error'); }
    });
  }

  var saveBtn = document.getElementById('ok-save-btn');
  if (saveBtn) saveBtn.addEventListener('click', function () { doSaveSettings(saveBtn); });

  var saveGlobal = document.getElementById('ok-save-global');
  if (saveGlobal) saveGlobal.addEventListener('click', function () { doSaveSettings(saveGlobal); });

  // ── Language prefixes table ────────────────────────────────────────────────
  (function () {
    var table  = document.getElementById('ok-lang-prefixes-table');
    var hidden = document.getElementById('ok-lang-prefixes-json');
    if (!table || !hidden) return;

    function serialize() {
      var rows = table.querySelectorAll('tbody tr');
      var result = [];
      rows.forEach(function (tr) {
        result.push({
          language_id: parseInt(tr.getAttribute('data-lang-id'), 10),
          code:        tr.getAttribute('data-lang-code'),
          prefix:      tr.querySelector('.ok-lang-prefix').value.trim(),
          default:     tr.querySelector('.ok-lang-default').checked
        });
      });
      hidden.value = JSON.stringify(result);
    }

    function init() {
      try {
        var data = JSON.parse(hidden.value || '[]');
        var map  = {};
        data.forEach(function (d) { map[d.language_id] = d; });
        table.querySelectorAll('tbody tr').forEach(function (tr) {
          var id  = parseInt(tr.getAttribute('data-lang-id'), 10);
          var def = map[id];
          if (!def) return;
          tr.querySelector('.ok-lang-prefix').value = def.prefix || '';
          if (def.default) tr.querySelector('.ok-lang-default').checked = true;
        });
      } catch (e) {}
    }

    init();
    table.addEventListener('input',  serialize);
    table.addEventListener('change', serialize);
  }());

  // ── Custom routes tables ───────────────────────────────────────────────────
  (function () {
    var skipTbody   = document.getElementById('ok-skip-routes-tbody');
    var entityTbody = document.getElementById('ok-entity-routes-tbody');
    var hidden      = document.getElementById('ok-custom-routes-json');
    if (!hidden) return;

    function makeDelBtn() {
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'ok-btn ok-btn-sm ok-btn-danger';
      btn.innerHTML = '<i data-lucide="trash-2"></i>';
      btn.addEventListener('click', function () {
        btn.closest('tr').remove();
        serialize();
        if (typeof lucide !== 'undefined') lucide.createIcons();
      });
      return btn;
    }

    function addSkipRow(val) {
      if (!skipTbody) return;
      var tr = document.createElement('tr');
      tr.innerHTML = '<td><input type="text" class="ok-input ok-input-sm ok-skip-route" value=""></td><td></td>';
      tr.querySelector('.ok-skip-route').value = val || '';
      tr.querySelector('td:last-child').appendChild(makeDelBtn());
      tr.querySelector('.ok-skip-route').addEventListener('input', serialize);
      skipTbody.appendChild(tr);
      if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function addEntityRow(id, route) {
      if (!entityTbody) return;
      var tr = document.createElement('tr');
      tr.innerHTML = '<td><input type="text" class="ok-input ok-input-sm ok-entity-id" placeholder="vendor_id" value=""></td>' +
                     '<td><input type="text" class="ok-input ok-input-sm ok-entity-route" placeholder="vendor/vendor/view" value=""></td>' +
                     '<td></td>';
      tr.querySelector('.ok-entity-id').value    = id    || '';
      tr.querySelector('.ok-entity-route').value = route || '';
      tr.querySelector('td:last-child').appendChild(makeDelBtn());
      tr.querySelector('.ok-entity-id').addEventListener('input',    serialize);
      tr.querySelector('.ok-entity-route').addEventListener('input', serialize);
      entityTbody.appendChild(tr);
      if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function serialize() {
      var skipRoutes = [];
      if (skipTbody) skipTbody.querySelectorAll('.ok-skip-route').forEach(function (el) {
        var v = el.value.trim();
        if (v) skipRoutes.push(v);
      });
      var entityRoutes = {};
      if (entityTbody) entityTbody.querySelectorAll('tr').forEach(function (tr) {
        var id    = (tr.querySelector('.ok-entity-id')    || {}).value || '';
        var route = (tr.querySelector('.ok-entity-route') || {}).value || '';
        if (id.trim() && route.trim()) entityRoutes[id.trim()] = route.trim();
      });
      hidden.value = JSON.stringify({ skip_routes: skipRoutes, entity_routes: entityRoutes });
    }

    function init() {
      try {
        var data = JSON.parse(hidden.value || '{}');
        (data.skip_routes || []).forEach(function (v) { addSkipRow(v); });
        var er = data.entity_routes || {};
        Object.keys(er).forEach(function (k) { addEntityRow(k, er[k]); });
      } catch (e) {}
    }

    var addSkipBtn   = document.getElementById('ok-skip-routes-add');
    var addEntityBtn = document.getElementById('ok-entity-routes-add');
    if (addSkipBtn)   addSkipBtn.addEventListener('click',   function () { addSkipRow('');    serialize(); });
    if (addEntityBtn) addEntityBtn.addEventListener('click', function () { addEntityRow('', ''); serialize(); });

    init();
  }());

  // ── Mask regeneration (settings page) ─────────────────────────────────────
  var regenBtn = document.getElementById('ok-regen-btn');
  if (regenBtn) {
    regenBtn.addEventListener('click', function () {
      var type    = (document.getElementById('ok-regen-type') || {}).value || 'product';
      var lang    = (document.getElementById('ok-regen-lang') || {}).value || '0';
      var mode    = (document.getElementById('ok-regen-mode') || {}).value || 'empty';
      var result  = document.getElementById('ok-regen-result');

      if (mode === 'all' && !confirm(i18n.confirm_regen_all || 'Overwrite all?')) return;

      regenBtn.disabled = true;
      var data = { entity_type: type, language_id: lang, mode: mode, user_token: cfg.userToken };
      okPost(cfg.maskRegenerateUrl, data, function (err, res) {
        regenBtn.disabled = false;
        if (err || !res) { window.okNotify && okNotify(i18n.error || 'Error', 'error'); return; }
        if (res.success && result) {
          result.classList.remove('ok-hidden');
          result.innerHTML = '<span class="ok-badge ok-badge-success">' +
            (i18n.regen_done || 'Done:') + ' ' +
            (res.inserted || 0) + ' ' + (i18n.regen_inserted || 'created') + ', ' +
            (res.updated  || 0) + ' ' + (i18n.regen_updated  || 'updated') + ', ' +
            (res.skipped  || 0) + ' ' + (i18n.regen_skipped  || 'skipped') +
            '</span>';
        }
      });
    });
  }

  // ── License page ───────────────────────────────────────────────────────────
  var activateBtn = document.getElementById('ok-activate-btn');
  if (activateBtn) {
    activateBtn.addEventListener('click', function () {
      var key = (document.getElementById('ok-license-key').value || '').trim();
      if (!key) { window.okNotify && okNotify(i18n.error_empty_key || 'Enter license key', 'warning'); return; }

      activateBtn.disabled = true;
      activateBtn.textContent = i18n.activating || 'Activating...';

      okPost(cfg.activateUrl, { key: key, user_token: cfg.userToken }, function (err, res) {
        activateBtn.disabled = false;
        activateBtn.innerHTML = '<i data-lucide="key"></i> Активувати';
        if (typeof lucide !== 'undefined') lucide.createIcons();

        var resultEl = document.getElementById('ok-license-result');
        if (err || !res) { resultEl.textContent = 'Error'; resultEl.classList.remove('ok-hidden'); return; }

        resultEl.textContent = res.message || '';
        resultEl.className = 'ok-license-result ' + (res.success ? 'ok-license-success' : 'ok-license-error');
        resultEl.classList.remove('ok-hidden');

        if (res.success && res.redirect_url) {
          setTimeout(function () { window.location.href = res.redirect_url; }, 1000);
        }
      });
    });
  }

  // ── Redirects page ─────────────────────────────────────────────────────────
  var redirectTbody = document.getElementById('ok-redirect-tbody');
  if (!redirectTbody) return;

  var currentPage  = 1;
  var searchTimer  = null;

  function loadRedirects(page, search) {
    page   = page   || 1;
    search = search || '';
    var url = cfg.redirectsListUrl + '&page=' + page + '&search=' + encodeURIComponent(search);
    okGet(url, function (err, res) {
      if (err || !res) return;
      renderRows(res.items || []);
      renderPager(res.total || 0, page);
    });
  }

  function renderRows(items) {
    if (!items.length) {
      redirectTbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Немає редиректів</td></tr>';
      return;
    }
    redirectTbody.innerHTML = items.map(function (r) {
      return '<tr data-id="' + r.redirect_id + '">' +
        '<td class="ok-editable">' + esc(r.from_url) + '</td>' +
        '<td class="ok-editable">' + esc(r.to_url) + '</td>' +
        '<td><span class="ok-badge ok-badge-' + codeBadgeClass(r.code) + '">' + r.code + '</span></td>' +
        '<td>' + r.hits + '</td>' +
        '<td>' + r.created_at.substr(0, 10) + '</td>' +
        '<td class="ok-col-actions">' +
          '<button class="ok-btn ok-btn-sm ok-btn-edit" data-id="' + r.redirect_id + '" data-from="' + esc(r.from_url) + '" data-to="' + esc(r.to_url) + '" data-code="' + r.code + '" data-expires="' + esc(r.expires_at || '') + '"><i data-lucide="pencil"></i></button>' +
          '<button class="ok-btn ok-btn-sm ok-btn-danger ok-btn-delete" data-id="' + r.redirect_id + '"><i data-lucide="trash-2"></i></button>' +
        '</td>' +
      '</tr>';
    }).join('');
    if (typeof lucide !== 'undefined') lucide.createIcons();
  }

  function renderPager(total, page) {
    var pager  = document.getElementById('ok-redirect-pagination');
    var pages  = Math.ceil(total / 50);
    if (pages <= 1) { pager.innerHTML = ''; return; }
    pager.innerHTML = buildPagerHtml(page, pages, 'data-page');
  }

  function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  // Search
  var searchEl = document.getElementById('ok-redirect-search');
  if (searchEl) {
    searchEl.addEventListener('input', function () {
      clearTimeout(searchTimer);
      var q = searchEl.value;
      searchTimer = setTimeout(function () { currentPage = 1; loadRedirects(1, q); }, 350);
    });
  }

  // Pagination
  document.addEventListener('click', function (e) {
    var pageBtn = e.target.closest('[data-page]');
    if (pageBtn && document.getElementById('ok-redirect-pagination').contains(pageBtn)) {
      e.preventDefault();
      currentPage = parseInt(pageBtn.dataset.page);
      loadRedirects(currentPage, searchEl ? searchEl.value : '');
    }
  });

  // Modal open/close helpers
  function openModal(id) { var el = document.getElementById(id); if (el) el.classList.remove('ok-hidden'); }
  function closeModal(id) { var el = document.getElementById(id); if (el) el.classList.add('ok-hidden'); }

  // Close modal on backdrop click, stop propagation on inner modal
  document.addEventListener('click', function (e) {
    if (e.target.classList.contains('ok-modal-backdrop')) {
      e.target.classList.add('ok-hidden');
      e.preventDefault();
      e.stopPropagation();
    }
  });
  document.querySelectorAll('.ok-modal').forEach(function (m) {
    m.addEventListener('click', function (e) { e.stopPropagation(); });
  });

  // Add button
  var addBtn = document.getElementById('ok-redirect-add');
  if (addBtn) {
    addBtn.addEventListener('click', function () {
      document.getElementById('ok-edit-id').value = '0';
      document.getElementById('ok-edit-from').value = '';
      document.getElementById('ok-edit-to').value = '';
      document.getElementById('ok-edit-code').value = '301';
      document.getElementById('ok-edit-code').dispatchEvent(new Event('change'));
      var expClear = document.getElementById('ok-edit-expires'); if (expClear) expClear.value = '';
      document.getElementById('ok-modal-title').textContent = 'Новий редирект';
      openModal('ok-redirect-modal');
    });
  }

  // Edit / delete via delegation
  document.addEventListener('click', function (e) {
    var editBtn = e.target.closest('.ok-btn-edit');
    if (editBtn && redirectTbody.contains(editBtn)) {
      document.getElementById('ok-edit-id').value   = editBtn.dataset.id;
      document.getElementById('ok-edit-from').value = editBtn.dataset.from;
      document.getElementById('ok-edit-to').value   = editBtn.dataset.to;
      document.getElementById('ok-edit-code').value = editBtn.dataset.code;
      document.getElementById('ok-edit-code').dispatchEvent(new Event('change'));
      var expLoad = document.getElementById('ok-edit-expires');
      if (expLoad) expLoad.value = (editBtn.dataset.expires || '').replace(' ', 'T').slice(0, 16);
      document.getElementById('ok-modal-title').textContent = 'Редагувати редирект';
      openModal('ok-redirect-modal');
    }

    var delBtn = e.target.closest('.ok-btn-delete');
    if (delBtn && redirectTbody.contains(delBtn)) {
      if (!confirm(i18n.confirm_delete || 'Delete?')) return;
      okPost(cfg.redirectDeleteUrl, { redirect_id: delBtn.dataset.id, user_token: cfg.userToken }, function (err, res) {
        if (!err && res && res.success) loadRedirects(currentPage, searchEl ? searchEl.value : '');
      });
    }
  });

  // Modal save
  var codeSel = document.getElementById('ok-edit-code');
  if (codeSel) {
    var updateCodeState = function () {
      var toInp   = document.getElementById('ok-edit-to');
      var helpEl  = document.getElementById('ok-edit-code-help');
      var code    = parseInt(codeSel.value, 10);
      if (toInp) {
        var is410 = code === 410;
        toInp.disabled = is410;
        if (is410) toInp.value = '';
      }
      if (helpEl) helpEl.textContent = i18n['code_' + code + '_use'] || '';
    };
    codeSel.addEventListener('change', updateCodeState);
    updateCodeState();
  }

  var modalSaveBtn = document.getElementById('ok-modal-save');
  if (modalSaveBtn) {
    modalSaveBtn.addEventListener('click', function () {
      var from = (document.getElementById('ok-edit-from').value || '').trim();
      var to   = (document.getElementById('ok-edit-to').value   || '').trim();
      var code = parseInt(document.getElementById('ok-edit-code').value, 10);
      if (!from || (code !== 410 && !to)) { window.okNotify && okNotify(i18n.error_fields || 'Fill fields', 'warning'); return; }

      var expEl = document.getElementById('ok-edit-expires');
      okPost(cfg.redirectSaveUrl, {
        redirect_id: document.getElementById('ok-edit-id').value,
        from_url: from,
        to_url: to,
        code: document.getElementById('ok-edit-code').value,
        expires_at: (expEl && expEl.value) ? expEl.value.replace('T', ' ') + ':00' : '',
        user_token: cfg.userToken
      }, function (err, res) {
        if (err || !res) return;
        if (res.success) {
          closeModal('ok-redirect-modal');
          loadRedirects(currentPage, searchEl ? searchEl.value : '');
          window.okNotify && okNotify('Збережено', 'success');
        } else {
          window.okNotify && okNotify(res.message || 'Error', 'error');
        }
      });
    });
  }

  document.getElementById('ok-modal-close')  && document.getElementById('ok-modal-close').addEventListener('click',  function () { closeModal('ok-redirect-modal'); });
  document.getElementById('ok-modal-cancel') && document.getElementById('ok-modal-cancel').addEventListener('click', function () { closeModal('ok-redirect-modal'); });

  // Import modal
  var importBtn = document.getElementById('ok-redirect-import');
  if (importBtn) {
    importBtn.addEventListener('click', function () {
      document.getElementById('ok-import-csv').value = '';
      openModal('ok-import-modal');
    });
  }

  var importSubmit = document.getElementById('ok-import-submit');
  if (importSubmit) {
    importSubmit.addEventListener('click', function () {
      var csv = document.getElementById('ok-import-csv').value;
      if (!csv.trim()) { window.okNotify && okNotify('CSV порожній', 'warning'); return; }
      okPost(cfg.redirectImportUrl, { csv: csv, user_token: cfg.userToken }, function (err, res) {
        if (err || !res) return;
        closeModal('ok-import-modal');
        loadRedirects(1, '');
        window.okNotify && okNotify((i18n.import_success || 'Imported:') + ' ' + (res.imported || 0) +
          (res.skipped ? ' / ' + (i18n.import_skipped || 'Skipped:') + ' ' + res.skipped : ''), 'success');
      });
    });
  }

  document.getElementById('ok-import-close')  && document.getElementById('ok-import-close').addEventListener('click',  function () { closeModal('ok-import-modal'); });
  document.getElementById('ok-import-cancel') && document.getElementById('ok-import-cancel').addEventListener('click', function () { closeModal('ok-import-modal'); });

  // Initial load
  loadRedirects(1, '');

}());

// ── Dashboard page ──────────────────────────────────────────────────────────
(function () {
  'use strict';
  var cfg  = window.seoCoreConfig || {};
  var i18n = window.seoCoreI18n  || {};

  var statSeoUrls = document.getElementById('stat-seo-urls');
  if (!statSeoUrls) return;

  function esc(s) {
    return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

  function loadDashboard() {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', cfg.dashboardStatsUrl, true);
    xhr.onload = function () {
      try {
        var res = JSON.parse(xhr.responseText);
        statSeoUrls.textContent = res.seo_urls || 0;
        document.getElementById('stat-redirects').textContent     = res.redirects      || 0;
        document.getElementById('stat-redirect-hits').textContent = res.redirect_hits  || 0;

        var scoreEl = document.getElementById('stat-seo-score');
        if (scoreEl) {
          scoreEl.textContent = (res.seo_score === null || res.seo_score === undefined) ? '—' : (res.seo_score + '/100');
          var card = scoreEl.closest('.ok-stat-card');
          if (card && res.seo_score !== null && res.seo_score !== undefined) {
            card.classList.remove('ok-stat-card-success', 'ok-stat-card-warning', 'ok-stat-card-danger');
            card.classList.add(res.seo_score >= 80 ? 'ok-stat-card-success'
                            : res.seo_score >= 50 ? 'ok-stat-card-warning'
                            : 'ok-stat-card-danger');
          }
        }

        // Audit stats
        var errEl  = document.getElementById('stat-audit-errors');
        var warnEl = document.getElementById('stat-audit-warnings');
        if (errEl)  errEl.textContent  = res.audit_errors   || 0;
        if (warnEl) warnEl.textContent = res.audit_warnings || 0;

        // Last run
        var lastRunEl = document.getElementById('ok-audit-last-run');
        if (lastRunEl && res.audit_last_run) {
          lastRunEl.textContent = 'Останній аудит: ' + res.audit_last_run;
        }

        // Audit top-10
        renderAuditTop(res.audit_top || []);

        // Chains
        var chainsCard = document.getElementById('stat-chains-card');
        var chainsEl   = document.getElementById('stat-chains');
        if (res.chains > 0) {
          if (chainsCard) chainsCard.classList.remove('ok-hidden');
          if (chainsEl)   chainsEl.textContent = res.chains;
          var flatBtn = document.getElementById('ok-flatten-chains');
          if (flatBtn) flatBtn.classList.remove('ok-hidden');
        }

        renderTopRedirects(res.top_redirects || []);
        renderChains(res.chain_details || []);
        if (typeof lucide !== 'undefined') lucide.createIcons();
      } catch (e) {}
    };
    xhr.send();
  }

  var _sevIcon = {
    error:   '<div class="ok-stat-icon"><i data-lucide="x"></i></div>',
    warning: '<div class="ok-stat-icon"><i data-lucide="triangle-alert"></i></div>',
    info:    '<div class="ok-stat-icon"><i data-lucide="info"></i></div>'
  };
  var _sevRowCls = { error: 'ok-row-error', warning: 'ok-row-warning', info: 'ok-row-info' };

  function renderAuditTop(items) {
    var tbody = document.getElementById('ok-audit-top-tbody');
    if (!tbody) return;
    if (!items.length) {
      tbody.innerHTML = '<tr><td colspan="5" class="text-center ok-muted">' + (i18n.text_audit_empty || '') + '</td></tr>';
      return;
    }

    // Group by entity (same rule as the Audit tab).
    var sevOrder = { error: 2, warning: 1, info: 0 };
    var map = {}, order = [];
    items.forEach(function (r) {
      var key = (r.entity_type || '') + ':' + (r.entity_id || '');
      if (!map[key]) {
        map[key] = {
          entity_type: r.entity_type,
          entity_id:   r.entity_id,
          entity_name: r.entity_name,
          severity:    r.severity,
          issues:      []
        };
        order.push(key);
      }
      var g = map[key];
      if ((sevOrder[r.severity] || 0) > (sevOrder[g.severity] || 0)) g.severity = r.severity;
      g.issues.push({ type: r.issue_type || '', detail: r.detail || '', severity: r.severity });
    });

    var labels = i18n.issue_labels || {};

    // Dedup badges by (issue_type + severity) within a row.
    function uniqueIssues(issues) {
      var seen = {}, out = [];
      issues.forEach(function (it) {
        var k = (it.type || '') + '|' + (it.severity || '');
        if (!seen[k]) { seen[k] = 1; out.push(it); }
      });
      return out;
    }

    function entityEditUrl(type, id) {
      var tpl = (cfg.entityEditUrls || {})[type];
      if (!tpl || !id) return '';
      return tpl.replace('__ID__', encodeURIComponent(id));
    }

    tbody.innerHTML = order.slice(0, 10).map(function (key) {
      var g = map[key];
      var issuesHtml = uniqueIssues(g.issues).map(function (it) {
        var sev = it.severity || 'info';
        var mod = sev === 'error' ? 'danger-soft'
                : sev === 'warning' ? 'warning-soft'
                : 'info-soft';
        var label = labels[it.type] || it.type;
        return '<span class="ok-badge ok-badge-' + mod + '" title="' + esc(it.detail || '') + '">' + esc(label) + '</span>';
      }).join(' ');

      var editUrl = entityEditUrl(g.entity_type, g.entity_id);
      var nameCell = editUrl
        ? '<a href="' + editUrl + '" target="_blank" class="ok-entity-link"><i data-lucide="external-link"></i>' + esc(g.entity_name || g.entity_id || '') + '</a>'
        : esc(g.entity_name || g.entity_id || '');

      return '<tr class="' + (_sevRowCls[g.severity] || '') + '">' +
        '<td>' + (_sevIcon[g.severity] || '') + '</td>' +
        '<td>' + esc(i18n['type_' + g.entity_type] || g.entity_type) + '</td>' +
        '<td>' + (g.entity_id || '') + '</td>' +
        '<td>' + nameCell + '</td>' +
        '<td class="ok-audit-issues">' + issuesHtml + '</td>' +
        '</tr>';
    }).join('');

    if (window.lucide) lucide.createIcons({ nodes: tbody.querySelectorAll('[data-lucide]') });
  }

  function renderTopRedirects(items) {
    var tbody = document.getElementById('ok-top-redirects');
    if (!tbody) return;
    if (!items.length) { tbody.innerHTML = '<tr><td colspan="4" class="text-center ok-muted">Немає переходів</td></tr>'; return; }
    tbody.innerHTML = items.map(function (r) {
      return '<tr><td>' + esc(r.from_url) + '</td><td>' + esc(r.to_url) + '</td>' +
        '<td><span class="ok-badge ok-badge-' + codeBadgeClass(r.code) + '">' + r.code + '</span></td>' +
        '<td>' + r.hits + '</td></tr>';
    }).join('');
  }

  function renderChains(chains) {
    var panel = document.getElementById('ok-chains-panel');
    var body  = document.getElementById('ok-chains-body');
    if (!panel || !body || !chains.length) { if (panel) panel.classList.add('ok-hidden'); return; }
    panel.classList.remove('ok-hidden');
    body.innerHTML = '<ul class="ok-chains-list">' + chains.map(function (hops) {
      return '<li>' + hops.map(esc).join(' → ') + '</li>';
    }).join('') + '</ul>';
  }

  var flattenBtn = document.getElementById('ok-flatten-chains');
  if (flattenBtn) {
    flattenBtn.addEventListener('click', function () {
      if (!confirm(i18n.confirm_flatten || 'Fix all redirect chains?')) return;
      flattenBtn.disabled = true;
      var xhr = new XMLHttpRequest();
      xhr.open('POST', cfg.flattenChainsUrl, true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.onload = function () {
        flattenBtn.disabled = false;
        try {
          var res = JSON.parse(xhr.responseText);
          if (res.success) {
            window.okNotify && window.okNotify((i18n.flatten_done || 'Fixed:') + ' ' + (res.updated || 0), 'success');
            loadDashboard();
          }
        } catch (e) {}
      };
      xhr.onerror = function () { flattenBtn.disabled = false; };
      xhr.send('user_token=' + encodeURIComponent(cfg.userToken));
    });
  }

  loadDashboard();
}());

// ── SEO URLs page ───────────────────────────────────────────────────────────
(function () {
  'use strict';
  var cfg  = window.seoCoreConfig || {};
  var i18n = window.seoCoreI18n  || {};

  var urlTbody = document.getElementById('ok-url-tbody');
  if (!urlTbody) return;

  var currentPage = 1;
  var searchTimer = null;

  function loadUrls(page, keyword, query, langId) {
    page    = page    || 1;
    keyword = keyword || '';
    query   = query   || '';
    langId  = langId  || '';
    var url = cfg.urlsListUrl + '&page=' + page +
      (keyword ? '&keyword=' + encodeURIComponent(keyword) : '') +
      (query   ? '&query='   + encodeURIComponent(query)   : '') +
      (langId  ? '&language_id=' + langId : '');
    var xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    xhr.onload = function () {
      try {
        var res = JSON.parse(xhr.responseText);
        renderUrlRows(res.items || []);
        renderUrlPager(res.total || 0, page);
      } catch (e) {}
    };
    xhr.send();
  }

  function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  var langMap = cfg.langMap || {};

  function langOptions(selectedId) {
    return Object.keys(langMap).map(function (id) {
      return '<option value="' + id + '"' + (String(id) === String(selectedId) ? ' selected' : '') + '>' + esc(langMap[id]) + '</option>';
    }).join('');
  }

  function saveUrlInline(id, keyword, query, langId) {
    var data = 'seo_url_id=' + encodeURIComponent(id) +
      '&keyword=' + encodeURIComponent(keyword) +
      '&query='   + encodeURIComponent(query) +
      '&language_id=' + encodeURIComponent(langId) +
      '&user_token=' + encodeURIComponent(cfg.userToken);
    var xhr = new XMLHttpRequest();
    xhr.open('POST', cfg.urlSaveUrl, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function () {
      try {
        var res = JSON.parse(xhr.responseText);
        if (res.success) { window.okNotify && okNotify(i18n.saved || 'Збережено', 'success'); }
        else { window.okNotify && okNotify(res.error || 'Помилка', 'error'); }
      } catch (e) {}
    };
    xhr.send(data);
  }

  function renderUrlRows(items) {
    if (!items.length) {
      urlTbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">' + (i18n.no_results || 'Немає записів') + '</td></tr>';
      return;
    }
    urlTbody.innerHTML = items.map(function (r) {
      var id = r.seo_url_id;
      return '<tr data-url-id="' + id + '">' +
        '<td><span class="ok-editable" contenteditable="true" data-url-field="keyword" data-url-id="' + id + '">' + esc(r.keyword) + '</span></td>' +
        '<td><span class="ok-editable" contenteditable="true" data-url-field="query" data-url-id="' + id + '">' + esc(r.query) + '</span></td>' +
        '<td><select class="ok-select ok-input-sm ok-url-lang-sel" data-url-id="' + id + '">' + langOptions(r.language_id) + '</select></td>' +
        '<td class="ok-col-actions">' +
          (function () {
            var hc = parseInt(r.history_count || 0, 10);
            var btnCls = hc > 0 ? 'ok-btn ok-btn-sm ok-btn-warning ok-url-history' : 'ok-btn ok-btn-sm ok-btn-default ok-url-history';
            var titleTxt = hc > 0
              ? ('Історія: ' + hc + ' зміна(и). Остання: ' + (r.last_old || '') + ' → ' + (r.last_new || '') + ' (' + (r.last_changed || '') + ')')
              : 'Історія змін (порожня)';
            return '<button class="' + btnCls + '" data-id="' + id + '" data-query="' + esc(r.query) + '" title="' + esc(titleTxt) + '"><i data-lucide="history"></i>' + (hc > 0 ? ('<span class="ok-badge-count">' + hc + '</span>') : '') + '</button>';
          })() +
          '<button class="ok-btn ok-btn-sm ok-btn-default ok-url-block" data-id="' + id + '" data-keyword="' + esc(r.keyword) + '" title="Заблокувати в robots.txt"><i data-lucide="shield-off"></i></button>' +
          '<button class="ok-btn ok-btn-sm ok-btn-default ok-url-gsc-submit" data-id="' + id + '" data-keyword="' + esc(r.keyword) + '" title="Submit to Google (Indexing API)"><i data-lucide="send"></i></button>' +
          '<button class="ok-btn ok-btn-sm ok-btn-danger ok-url-delete" data-id="' + id + '"><i data-lucide="trash-2"></i></button>' +
        '</td>' +
      '</tr>';
    }).join('');
    urlTbody.querySelectorAll('[contenteditable]').forEach(function (el) { el._origVal = el.textContent.trim(); });
    if (typeof lucide !== 'undefined') lucide.createIcons();
  }

  document.addEventListener('focusout', function (e) {
    var el = e.target;
    if (!el.dataset || !el.dataset.urlField) return;
    var curr = el.textContent.trim();
    if (curr === el._origVal) return;
    el._origVal = curr;
    var tr  = el.closest('tr[data-url-id]');
    if (!tr) return;
    var id  = tr.dataset.urlId;
    var kw  = tr.querySelector('[data-url-field="keyword"]').textContent.trim();
    var q   = tr.querySelector('[data-url-field="query"]').textContent.trim();
    var lng = tr.querySelector('.ok-url-lang-sel').value;
    saveUrlInline(id, kw, q, lng);
  });

  document.addEventListener('change', function (e) {
    var sel = e.target.closest('.ok-url-lang-sel');
    if (!sel) return;
    var tr  = sel.closest('tr[data-url-id]');
    if (!tr) return;
    var id  = tr.dataset.urlId;
    var kw  = tr.querySelector('[data-url-field="keyword"]').textContent.trim();
    var q   = tr.querySelector('[data-url-field="query"]').textContent.trim();
    saveUrlInline(id, kw, q, sel.value);
  });

  function renderUrlPager(total, page) {
    var pager = document.getElementById('ok-url-pagination');
    var pages = Math.ceil(total / 50);
    if (!pager || pages <= 1) { if (pager) pager.innerHTML = ''; return; }
    pager.innerHTML = buildPagerHtml(page, pages, 'data-url-page');
  }

  var searchKeyword = document.getElementById('ok-url-search-keyword');
  var searchQuery   = document.getElementById('ok-url-search-query');
  var langSel       = document.getElementById('ok-url-filter-lang');

  function getUrlSearch() {
    return {
      keyword: searchKeyword ? searchKeyword.value : '',
      query:   searchQuery   ? searchQuery.value   : '',
      lang:    langSel       ? langSel.value        : ''
    };
  }

  if (searchKeyword) {
    searchKeyword.addEventListener('input', function () {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () { var s = getUrlSearch(); currentPage = 1; loadUrls(1, s.keyword, s.query, s.lang); }, 350);
    });
  }
  if (searchQuery) {
    searchQuery.addEventListener('input', function () {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () { var s = getUrlSearch(); currentPage = 1; loadUrls(1, s.keyword, s.query, s.lang); }, 350);
    });
  }
  if (langSel) {
    langSel.addEventListener('change', function () { var s = getUrlSearch(); currentPage = 1; loadUrls(1, s.keyword, s.query, s.lang); });
  }

  document.addEventListener('click', function (e) {
    var pb = e.target.closest('[data-url-page]');
    if (pb) {
      e.preventDefault();
      currentPage = parseInt(pb.dataset.urlPage);
      var s = getUrlSearch(); loadUrls(currentPage, s.keyword, s.query, s.lang);
    }

    var delBtn = e.target.closest('.ok-url-delete');
    if (delBtn && urlTbody.contains(delBtn)) {
      if (!confirm(i18n.confirm_delete_url || 'Delete?')) return;
      var xhr = new XMLHttpRequest();
      xhr.open('POST', cfg.urlDeleteUrl, true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.onload = function () { var s = getUrlSearch(); loadUrls(currentPage, s.keyword, s.query, s.lang); };
      xhr.send('seo_url_id=' + delBtn.dataset.id + '&user_token=' + encodeURIComponent(cfg.userToken));
    }

    var histBtn = e.target.closest('.ok-url-history');
    if (histBtn && urlTbody.contains(histBtn)) {
      openUrlHistory(histBtn.dataset.id, histBtn.dataset.query);
    }

    var gscBtn = e.target.closest('.ok-url-gsc-submit');
    if (gscBtn && urlTbody.contains(gscBtn)) {
      var origin = window.location.protocol + '//' + window.location.hostname.replace(/^admin\./, '');
      var fullUrl = origin + '/' + gscBtn.dataset.keyword;
      if (!confirm('Submit to Google Indexing API?\n' + fullUrl)) return;
      var fd = new FormData();
      fd.append('url', fullUrl);
      fd.append('type', 'updated');
      fd.append('user_token', cfg.userToken);
      var xhr = new XMLHttpRequest();
      xhr.open('POST', cfg.gscSubmitUrlUrl, true);
      xhr.onload = function () {
        try {
          var r = JSON.parse(xhr.responseText);
          window.okNotify && okNotify(r.success ? 'Submitted to Google' : (r.message || 'Error'), r.success ? 'success' : 'error');
        } catch (e) {}
      };
      xhr.send(fd);
    }

    var blockBtn = e.target.closest('.ok-url-block');
    if (blockBtn && urlTbody.contains(blockBtn)) {
      if (!confirm('Додати "Disallow: /' + blockBtn.dataset.keyword + '" у robots.txt?')) return;
      var fd = new FormData();
      fd.append('seo_url_id', blockBtn.dataset.id);
      fd.append('user_token', cfg.userToken);
      var xhr = new XMLHttpRequest();
      xhr.open('POST', cfg.urlBlockRobotsUrl, true);
      xhr.onload = function () {
        try {
          var r = JSON.parse(xhr.responseText);
          if (r.success) {
            window.okNotify && okNotify(r.already ? ('Уже заблоковано: ' + r.line) : ('Додано в robots.txt: ' + r.line), 'success');
          } else {
            window.okNotify && okNotify(r.error || 'Error', 'error');
          }
        } catch (e) {}
      };
      xhr.send(fd);
    }
  });

  function openUrlHistory(seoUrlId, query) {
    var modal  = document.getElementById('ok-url-history-modal');
    var tbody  = document.getElementById('ok-url-history-tbody');
    var sub    = document.getElementById('ok-url-history-subtitle');
    if (!modal || !tbody) return;
    sub.textContent = 'query: ' + (query || '');
    tbody.innerHTML = '<tr><td colspan="4" class="text-center ok-loading">' + (i18n.loading || 'Завантаження…') + '</td></tr>';
    modal.classList.remove('ok-hidden');

    var xhr = new XMLHttpRequest();
    xhr.open('GET', cfg.urlHistoryListUrl + '&seo_url_id=' + encodeURIComponent(seoUrlId), true);
    xhr.onload = function () {
      try {
        var r = JSON.parse(xhr.responseText);
        var items = r.items || [];
        if (!items.length) {
          tbody.innerHTML = '<tr><td colspan="4" class="text-center ok-muted">' + (i18n.no_results || 'Немає історії') + '</td></tr>';
          return;
        }
        tbody.innerHTML = items.map(function (h) {
          return '<tr>' +
            '<td><code>' + esc(h.old_keyword) + '</code></td>' +
            '<td><code>' + esc(h.new_keyword) + '</code></td>' +
            '<td>' + esc(h.changed_at) + '</td>' +
            '<td class="ok-col-actions"><button class="ok-btn ok-btn-sm ok-btn-default ok-url-rollback" data-history-id="' + h.history_id + '" title="Відкотити до старого keyword"><i data-lucide="undo-2"></i></button></td>' +
            '</tr>';
        }).join('');
        if (typeof lucide !== 'undefined') lucide.createIcons();
      } catch (e) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center ok-text-error">Помилка завантаження</td></tr>';
      }
    };
    xhr.send();
  }

  document.addEventListener('click', function (e) {
    var rb = e.target.closest('.ok-url-rollback');
    if (rb) {
      if (!confirm('Відкотити keyword до попереднього значення?')) return;
      var xhr = new XMLHttpRequest();
      xhr.open('POST', cfg.urlHistoryRollbackUrl, true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.onload = function () {
        try {
          var r = JSON.parse(xhr.responseText);
          if (r.success) {
            window.okNotify && okNotify(i18n.rolled_back || 'Відкочено', 'success');
            document.getElementById('ok-url-history-modal').classList.add('ok-hidden');
            var s = getUrlSearch(); loadUrls(currentPage, s.keyword, s.query, s.lang);
          } else {
            window.okNotify && okNotify(r.error || 'Error', 'error');
          }
        } catch (e) {}
      };
      xhr.send('history_id=' + rb.dataset.historyId + '&user_token=' + encodeURIComponent(cfg.userToken));
    }
    var cls = e.target.closest('#ok-url-history-close,#ok-url-history-cancel');
    if (cls) document.getElementById('ok-url-history-modal').classList.add('ok-hidden');
  });

  var addUrlBtn = document.getElementById('ok-url-add');
  if (addUrlBtn) {
    addUrlBtn.addEventListener('click', function () {
      document.getElementById('ok-url-edit-id').value      = '0';
      document.getElementById('ok-url-edit-keyword').value = '';
      document.getElementById('ok-url-edit-query').value   = '';
      document.getElementById('ok-url-modal-title').textContent = 'Новий SEO URL';
      document.getElementById('ok-url-modal').classList.remove('ok-hidden');
    });
  }

  var urlModalSave = document.getElementById('ok-url-modal-save');
  if (urlModalSave) {
    urlModalSave.addEventListener('click', function () {
      var kw = (document.getElementById('ok-url-edit-keyword').value || '').trim();
      var q  = (document.getElementById('ok-url-edit-query').value   || '').trim();
      if (!kw || !q) { window.okNotify && okNotify(i18n.error_url_fields || 'Fill fields', 'warning'); return; }

      var data = 'seo_url_id=' + document.getElementById('ok-url-edit-id').value +
        '&keyword=' + encodeURIComponent(kw) +
        '&query='   + encodeURIComponent(q) +
        '&language_id=' + encodeURIComponent(document.getElementById('ok-url-edit-lang').value) +
        '&user_token=' + encodeURIComponent(cfg.userToken);

      var xhr = new XMLHttpRequest();
      xhr.open('POST', cfg.urlSaveUrl, true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.onload = function () {
        try {
          var res = JSON.parse(xhr.responseText);
          if (res.success) {
            document.getElementById('ok-url-modal').classList.add('ok-hidden');
            var s = getUrlSearch(); loadUrls(currentPage, s.keyword, s.query, s.lang);
            window.okNotify && okNotify('Збережено', 'success');
          }
        } catch (e) {}
      };
      xhr.send(data);
    });
  }

  document.getElementById('ok-url-modal-close')  && document.getElementById('ok-url-modal-close').addEventListener('click',  function () { document.getElementById('ok-url-modal').classList.add('ok-hidden'); });
  document.getElementById('ok-url-modal-cancel') && document.getElementById('ok-url-modal-cancel').addEventListener('click', function () { document.getElementById('ok-url-modal').classList.add('ok-hidden'); });

  loadUrls(1, '', '', '');

}());

// ── Meta overrides page ─────────────────────────────────────────────────────
(function () {
  'use strict';
  var cfg  = window.seoCoreConfig || {};
  var i18n = window.seoCoreI18n  || {};

  var metaTbody = document.getElementById('ok-meta-tbody');
  if (!metaTbody) return;

  var currentPage = 1;
  var searchTimer = null;

  function loadMeta(page, search, type, lang) {
    var url = cfg.metaListUrl +
      '&page=' + (page || 1) +
      '&search=' + encodeURIComponent(search || '') +
      (type ? '&entity_type=' + encodeURIComponent(type) : '') +
      (lang ? '&language_id=' + lang : '');
    var xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    xhr.onload = function () {
      try {
        var res = JSON.parse(xhr.responseText);
        renderMeta(res.items || []);
        renderMetaPager(res.total || 0, page || 1);
      } catch(e) {}
    };
    xhr.send();
  }

  function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

  function renderMeta(items) {
    if (!items.length) { metaTbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Немає записів</td></tr>'; return; }
    metaTbody.innerHTML = items.map(function(r) {
      return '<tr>' +
        '<td><span class="ok-badge ok-badge-secondary">' + esc(r.entity_type) + '</span></td>' +
        '<td>' + r.entity_id + '</td>' +
        '<td class="ok-editable">' + esc(r.title || '') + '</td>' +
        '<td class="ok-editable ok-text-truncate">' + esc((r.description || '').substring(0, 80)) + '</td>' +
        '<td>' + r.language_id + '</td>' +
        '<td class="ok-col-actions">' +
          '<button class="ok-btn ok-btn-sm ok-meta-edit" ' +
            'data-id="' + r.meta_id + '" data-type="' + esc(r.entity_type) + '" ' +
            'data-eid="' + r.entity_id + '" data-lang="' + r.language_id + '" ' +
            'data-title="' + esc(r.title || '') + '" data-desc="' + esc(r.description || '') + '" ' +
            'data-h1="' + esc(r.h1 || '') + '" data-robots="' + esc(r.robots || '') + '" ' +
            'data-canonical="' + esc(r.canonical || '') + '">' +
            '<i data-lucide="pencil"></i></button>' +
          '<button class="ok-btn ok-btn-sm ok-btn-danger ok-meta-delete" data-id="' + r.meta_id + '"><i data-lucide="trash-2"></i></button>' +
        '</td></tr>';
    }).join('');
    if (typeof lucide !== 'undefined') lucide.createIcons();
  }

  function renderMetaPager(total, page) {
    var pager = document.getElementById('ok-meta-pagination');
    var pages = Math.ceil(total / 50);
    if (!pager || pages <= 1) { if (pager) pager.innerHTML = ''; return; }
    pager.innerHTML = buildPagerHtml(page, pages, 'data-meta-page');
  }

  var metaSearch = document.getElementById('ok-meta-search');
  var metaType   = document.getElementById('ok-meta-filter-type');
  var metaLang   = document.getElementById('ok-meta-filter-lang');

  function reloadMeta() { currentPage = 1; loadMeta(1, metaSearch ? metaSearch.value : '', metaType ? metaType.value : '', metaLang ? metaLang.value : ''); }

  if (metaSearch) metaSearch.addEventListener('input', function() { clearTimeout(searchTimer); searchTimer = setTimeout(reloadMeta, 350); });
  if (metaType)   metaType.addEventListener('change', reloadMeta);
  if (metaLang)   metaLang.addEventListener('change', reloadMeta);

  document.addEventListener('click', function(e) {
    var pb = e.target.closest('[data-meta-page]');
    if (pb) { e.preventDefault(); currentPage = parseInt(pb.dataset.metaPage); loadMeta(currentPage, metaSearch ? metaSearch.value : '', metaType ? metaType.value : '', metaLang ? metaLang.value : ''); }

    var editBtn = e.target.closest('.ok-meta-edit');
    if (editBtn && metaTbody.contains(editBtn)) {
      var d = editBtn.dataset;
      document.getElementById('ok-meta-edit-id').value        = d.id;
      document.getElementById('ok-meta-edit-type').value      = d.type;
      document.getElementById('ok-meta-edit-eid').value       = d.eid;
      document.getElementById('ok-meta-edit-lang').value      = d.lang;
      var entityName = document.getElementById('ok-meta-edit-entity-name');
      if (entityName) entityName.value = (d.name || d.title || '') + ' (#' + d.eid + ')';
      document.getElementById('ok-meta-edit-title').value     = d.title;
      document.getElementById('ok-meta-edit-desc').value      = d.desc;
      document.getElementById('ok-meta-edit-h1').value        = d.h1;
      document.getElementById('ok-meta-edit-robots').value    = d.robots;
      document.getElementById('ok-meta-edit-canonical').value = d.canonical;
      document.getElementById('ok-meta-modal').classList.remove('ok-hidden');
    }

    var delBtn = e.target.closest('.ok-meta-delete');
    if (delBtn && metaTbody.contains(delBtn)) {
      if (!confirm(i18n.confirm_delete_meta || 'Delete?')) return;
      var xhr = new XMLHttpRequest();
      xhr.open('POST', cfg.metaDeleteUrl, true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.onload = function () { reloadMeta(); };
      xhr.send('meta_id=' + delBtn.dataset.id + '&user_token=' + encodeURIComponent(cfg.userToken));
    }
  });

  var metaModalSave = document.getElementById('ok-meta-modal-save');
  if (metaModalSave) {
    metaModalSave.addEventListener('click', function() {
      var data = 'meta_id='         + encodeURIComponent(document.getElementById('ok-meta-edit-id').value) +
                 '&entity_type='    + encodeURIComponent(document.getElementById('ok-meta-edit-type').value) +
                 '&entity_id='      + encodeURIComponent(document.getElementById('ok-meta-edit-eid').value) +
                 '&language_id='    + encodeURIComponent(document.getElementById('ok-meta-edit-lang').value) +
                 '&title='          + encodeURIComponent(document.getElementById('ok-meta-edit-title').value) +
                 '&description='    + encodeURIComponent(document.getElementById('ok-meta-edit-desc').value) +
                 '&h1='             + encodeURIComponent(document.getElementById('ok-meta-edit-h1').value) +
                 '&robots='         + encodeURIComponent(document.getElementById('ok-meta-edit-robots').value) +
                 '&canonical='      + encodeURIComponent(document.getElementById('ok-meta-edit-canonical').value) +
                 '&user_token='     + encodeURIComponent(cfg.userToken);
      var xhr = new XMLHttpRequest();
      xhr.open('POST', cfg.metaSaveUrl, true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.onload = function() {
        document.getElementById('ok-meta-modal').classList.add('ok-hidden');
        reloadMeta();
        window.okNotify && okNotify('Збережено', 'success');
      };
      xhr.send(data);
    });
  }

  document.getElementById('ok-meta-modal-close')  && document.getElementById('ok-meta-modal-close').addEventListener('click',  function() { document.getElementById('ok-meta-modal').classList.add('ok-hidden'); });
  document.getElementById('ok-meta-modal-cancel') && document.getElementById('ok-meta-modal-cancel').addEventListener('click', function() { document.getElementById('ok-meta-modal').classList.add('ok-hidden'); });

  // ── Bulk fill — category filter + vars hint ─────────────────────────────────
  var bulkTypeEl    = document.getElementById('ok-bulk-type');
  var bulkCatWrap   = document.getElementById('ok-bulk-cat-wrap');
  var bulkCatInput  = document.getElementById('ok-bulk-category');
  var bulkCatIdEl   = document.getElementById('ok-bulk-category-id');
  var bulkCatDrop   = document.getElementById('ok-bulk-cat-drop');
  var bulkVarsHint  = document.getElementById('ok-bulk-vars-hint');
  var bulkCatTimer  = null;

  function updateBulkVarsHint() {
    if (!bulkVarsHint || !bulkTypeEl) return;
    bulkVarsHint.innerHTML = i18n['meta_vars_' + bulkTypeEl.value] || '';
  }

  function toggleBulkCatWrap() {
    if (!bulkCatWrap || !bulkTypeEl) return;
    bulkCatWrap.style.display = (bulkTypeEl.value === 'product') ? '' : 'none';
    if (bulkTypeEl.value !== 'product' && bulkCatIdEl) { bulkCatIdEl.value = '0'; if (bulkCatInput) bulkCatInput.value = ''; }
  }

  if (bulkTypeEl) {
    bulkTypeEl.addEventListener('change', function() { toggleBulkCatWrap(); updateBulkVarsHint(); });
    toggleBulkCatWrap();
    updateBulkVarsHint();

    // Sync visible button-group to the hidden <select>
    var bulkTypeGroup = document.getElementById('ok-bulk-type-group');
    if (bulkTypeGroup) {
      bulkTypeGroup.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-type]');
        if (!btn) return;
        var type = btn.getAttribute('data-type');
        bulkTypeGroup.querySelectorAll('[data-type]').forEach(function (b) {
          b.classList.toggle('active', b === btn);
        });
        bulkTypeGroup.setAttribute('data-value', type);
        bulkTypeEl.value = type;
        bulkTypeEl.dispatchEvent(new Event('change'));
      });
    }
  }

  if (bulkCatInput && cfg.ajaxCategories) {
    bulkCatInput.addEventListener('input', function() {
      clearTimeout(bulkCatTimer);
      var q = bulkCatInput.value.trim();
      if (!q) { bulkCatIdEl.value = '0'; bulkCatDrop.classList.add('ok-hidden'); return; }
      bulkCatTimer = setTimeout(function() {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', cfg.ajaxCategories + '&q=' + encodeURIComponent(q), true);
        xhr.onload = function() {
          try {
            var res = JSON.parse(xhr.responseText);
            if (!res.length) { bulkCatDrop.classList.add('ok-hidden'); return; }
            bulkCatDrop.innerHTML = res.map(function(r) {
              return '<div class="ok-autocomplete-item" data-id="' + r.id + '" data-text="' + r.text.replace(/"/g,'&quot;') + '">' + r.text + '</div>';
            }).join('');
            bulkCatDrop.classList.remove('ok-hidden');
          } catch(e) {}
        };
        xhr.send();
      }, 300);
    });
    document.addEventListener('click', function(e) {
      var item = e.target.closest('.ok-autocomplete-item');
      if (item && bulkCatDrop && bulkCatDrop.contains(item)) {
        bulkCatIdEl.value = item.dataset.id;
        bulkCatInput.value = item.dataset.text;
        bulkCatDrop.classList.add('ok-hidden');
      } else if (!bulkCatInput.contains(e.target)) {
        bulkCatDrop && bulkCatDrop.classList.add('ok-hidden');
      }
    });
  }

  // ── Bulk fill ───────────────────────────────────────────────────────────────
  var bulkBtn = document.getElementById('ok-bulk-start');
  if (bulkBtn) {
    bulkBtn.addEventListener('click', function() {
      var type   = document.getElementById('ok-bulk-type').value;
      var lang   = document.getElementById('ok-bulk-lang').value;
      var mode   = document.getElementById('ok-bulk-mode').value;
      var catId  = bulkCatIdEl ? (bulkCatIdEl.value || '0') : '0';
      var url    = cfg.bulkCandidatesUrl + '&entity_type=' + type + '&language_id=' + lang + '&mode=' + mode + (catId !== '0' ? '&category_id=' + catId : '');

      bulkBtn.disabled = true;
      var xhr = new XMLHttpRequest();
      xhr.open('GET', url, true);
      xhr.onload = function() {
        try {
          var res = JSON.parse(xhr.responseText);
          runBulk(res.items || [], type, lang, mode);
        } catch(e) { bulkBtn.disabled = false; }
      };
      xhr.send();
    });
  }

  function runBulk(items, type, lang, mode) {
    if (!items.length) { bulkBtn.disabled = false; window.okNotify && okNotify('Нічого не знайдено', 'info'); return; }
    var progress = document.getElementById('ok-bulk-progress');
    var bar      = document.getElementById('ok-bulk-bar');
    var status   = document.getElementById('ok-bulk-status');
    if (progress) progress.classList.remove('ok-hidden');

    var total    = items.length;
    var done     = 0;
    var batchSize = 50;

    function sendBatch(offset) {
      var batch = items.slice(offset, offset + batchSize).map(function(i) { return i.entity_id; });
      var data  = 'entity_ids[]=' + batch.join('&entity_ids[]=') +
                  '&entity_type=' + encodeURIComponent(type) +
                  '&language_id=' + lang + '&mode=' + mode +
                  '&user_token='  + encodeURIComponent(cfg.userToken);
      var xhr = new XMLHttpRequest();
      xhr.open('POST', cfg.bulkFillUrl, true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.onload = function() {
        done += batch.length;
        var pct = Math.round(done / total * 100);
        if (bar) bar.style.width = pct + '%';
        if (status) status.textContent = done + ' / ' + total;

        if (done < total) {
          sendBatch(offset + batchSize);
        } else {
          bulkBtn.disabled = false;
          try {
            var res = JSON.parse(xhr.responseText);
            window.okNotify && okNotify(
              (i18n.bulk_complete || 'Done:') + ' ' + (res.filled || 0) + ' ' + (i18n.bulk_filled || 'filled') +
              ', ' + (res.skipped || 0) + ' ' + (i18n.bulk_skipped || 'skipped'), 'success'
            );
          } catch(e) {}
          reloadMeta();
        }
      };
      xhr.onerror = function() { bulkBtn.disabled = false; };
      xhr.send(data);
    }

    sendBatch(0);
  }

  // ── Meta templates type selector + language tabs ──────────────────────────
  (function () {
    var typeSel = document.getElementById('ok-meta-tpl-type-sel');
    if (!typeSel) return;

    function showTypeGroup(type) {
      document.querySelectorAll('.ok-meta-tpl-group').forEach(function (el) {
        el.classList.toggle('ok-hidden', el.getAttribute('data-tpl-type') !== type);
      });
    }

    function activateLangTab(btn) {
      var key = btn.getAttribute('data-lang-tab');
      var container = btn.closest('.ok-meta-tpl-group');
      container.querySelectorAll('.ok-lang-tab-btn').forEach(function (b) { b.classList.remove('active'); });
      container.querySelectorAll('.ok-lang-tab-panel').forEach(function (p) { p.classList.add('ok-hidden'); });
      btn.classList.add('active');
      var panel = container.querySelector('[data-lang-panel="' + key + '"]');
      if (panel) panel.classList.remove('ok-hidden');
    }

    typeSel.addEventListener('change', function () { showTypeGroup(typeSel.value); });

    // Pill-style type switcher (mirrors hidden select)
    var typeGroup = document.getElementById('ok-meta-tpl-type-group');
    if (typeGroup) {
      typeGroup.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-type]');
        if (!btn) return;
        var type = btn.getAttribute('data-type');
        typeGroup.querySelectorAll('[data-type]').forEach(function (b) {
          b.classList.toggle('active', b === btn);
        });
        typeGroup.setAttribute('data-value', type);
        typeSel.value = type;
        showTypeGroup(type);
      });
    }

    document.addEventListener('click', function (e) {
      var tabBtn = e.target.closest('.ok-lang-tab-btn[data-lang-tab]');
      if (tabBtn) activateLangTab(tabBtn);
    });

    showTypeGroup(typeSel.value);
  }());

  // ── "Add override" button opens meta modal with blank fields ──────────────
  var metaAddBtn = document.getElementById('ok-meta-add');
  if (metaAddBtn) {
    metaAddBtn.addEventListener('click', function () {
      document.getElementById('ok-meta-edit-id').value        = '0';
      document.getElementById('ok-meta-edit-type').value      = 'product';
      document.getElementById('ok-meta-edit-eid').value       = '0';
      var entityNameInput = document.getElementById('ok-meta-edit-entity-name');
      if (entityNameInput) entityNameInput.value = '';
      // default to store's config language (already selected in Twig via selected attr) — leave untouched on Add
      document.getElementById('ok-meta-edit-title').value     = '';
      document.getElementById('ok-meta-edit-desc').value      = '';
      document.getElementById('ok-meta-edit-h1').value        = '';
      document.getElementById('ok-meta-edit-robots').value    = '';
      document.getElementById('ok-meta-edit-canonical').value = '';
      document.getElementById('ok-meta-modal').classList.remove('ok-hidden');
    });
  }

  // ── Meta override modal — entity autocomplete ─────────────────────────────
  (function () {
    var nameInput = document.getElementById('ok-meta-edit-entity-name');
    var idInput   = document.getElementById('ok-meta-edit-eid');
    var drop      = document.getElementById('ok-meta-edit-entity-drop');
    var typeSel   = document.getElementById('ok-meta-edit-type');
    if (!nameInput || !drop || !cfg.ajaxEntitiesUrl) return;

    var timer = null;
    nameInput.addEventListener('input', function () {
      clearTimeout(timer);
      idInput.value = '0';
      var q = nameInput.value.trim();
      if (q.length < 1) { drop.classList.add('ok-hidden'); return; }
      timer = setTimeout(function () {
        var xhr = new XMLHttpRequest();
        var langId = document.getElementById('ok-meta-edit-lang').value || '1';
        xhr.open('GET', cfg.ajaxEntitiesUrl + '&type=' + encodeURIComponent(typeSel.value)
                 + '&language_id=' + encodeURIComponent(langId)
                 + '&q=' + encodeURIComponent(q), true);
        xhr.onload = function () {
          try {
            var res = JSON.parse(xhr.responseText);
            if (!res.length) { drop.classList.add('ok-hidden'); return; }
            drop.innerHTML = res.map(function (r) {
              return '<div class="ok-autocomplete-item" data-id="' + r.id +
                     '" data-text="' + String(r.text).replace(/"/g, '&quot;') + '">' + r.text + '</div>';
            }).join('');
            drop.classList.remove('ok-hidden');
          } catch(e) {}
        };
        xhr.send();
      }, 250);
    });

    // Clear id when user edits text after selection
    nameInput.addEventListener('focus', function () { if (nameInput.value && idInput.value !== '0') drop.classList.remove('ok-hidden'); });

    drop.addEventListener('click', function (e) {
      var item = e.target.closest('.ok-autocomplete-item');
      if (!item) return;
      idInput.value  = item.getAttribute('data-id');
      nameInput.value = item.getAttribute('data-text');
      drop.classList.add('ok-hidden');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function (e) {
      if (!e.target.closest('#ok-meta-edit-entity-drop') && !e.target.closest('#ok-meta-edit-entity-name')) {
        drop.classList.add('ok-hidden');
      }
    });

    // Reset on type switch
    if (typeSel) typeSel.addEventListener('change', function () {
      idInput.value = '0';
      nameInput.value = '';
      drop.classList.add('ok-hidden');
    });
  }());

  loadMeta(1, '', '', '');
}());

// ── Audit page ─────────────────────────────────────────────────────────────────
(function () {
  'use strict';
  var cfg  = window.seoCoreConfig || {};
  var i18n = window.seoCoreI18n || {};

  var runBtn       = document.getElementById('ok-audit-run');
  var spinner      = document.getElementById('ok-audit-spinner');
  var summary      = document.getElementById('ok-audit-summary');
  var runBody      = document.getElementById('ok-audit-run-body');
  var filterLang   = document.getElementById('ok-audit-filter-lang');
  var resultsPanel = document.getElementById('ok-audit-results-panel');
  var tbody        = document.getElementById('ok-audit-tbody');
  var emptyMsg     = document.getElementById('ok-audit-empty');
  var langSel      = document.getElementById('ok-audit-lang');
  var filterSev    = document.getElementById('ok-audit-filter-severity');
  var filterType   = document.getElementById('ok-audit-filter-type');
  var filterLimit  = document.getElementById('ok-audit-filter-limit');
  var checkAll     = document.getElementById('ok-audit-check-all');
  var bulkBar      = document.getElementById('ok-audit-bulk-bar');
  var bulkDelete   = document.getElementById('ok-audit-bulk-delete');
  var selCount     = document.getElementById('ok-audit-selected-count');

  if (!runBtn) return;

  var lastLangId = langSel ? langSel.value : '0';

  var sevIcon = {
    error:   '<i data-lucide="x"></i>',
    warning: '<i data-lucide="triangle-alert"></i>',
    info:    '<i data-lucide="info"></i>'
  };
  var sevRowClass = { error: 'ok-row-error', warning: 'ok-row-warning', info: 'ok-row-info' };

  function updateBulkBar() {
    var checked = tbody ? tbody.querySelectorAll('.ok-audit-check:checked').length : 0;
    if (bulkBar) bulkBar.classList.toggle('ok-hidden', checked === 0);
    if (selCount) selCount.textContent = checked + ' ' + (i18n.text_selected || '');
    if (checkAll) checkAll.indeterminate = checked > 0 && checked < (tbody ? tbody.querySelectorAll('.ok-audit-check').length : 0);
  }

  var PER_PAGE = 50;
  function currentPerPage() {
    return filterLimit ? parseInt(filterLimit.value, 10) || PER_PAGE : PER_PAGE;
  }
  var auditAllItems = [];
  var auditPage = 1;
  var auditPager = document.getElementById('ok-audit-pagination');

  var sevOrder = { error: 2, warning: 1, info: 0 };

  function groupByEntity(items) {
    var map = {}, order = [];
    var statusOrder = { new: 0, in_progress: 1, fixed: 2, ignored: 3 };
    items.forEach(function(r) {
      var key = (r.entity_type || '') + ':' + (r.entity_id || '');
      if (!map[key]) {
        map[key] = { entity_type: r.entity_type, entity_id: r.entity_id, entity_name: r.entity_name, severity: r.severity, status: r.status || 'new', issues: [], ids: [] };
        order.push(key);
      }
      var g = map[key];
      if ((sevOrder[r.severity] || 0) > (sevOrder[g.severity] || 0)) g.severity = r.severity;
      // Worst (least "done") status wins for the group
      if ((statusOrder[r.status] || 0) < (statusOrder[g.status] || 0)) g.status = r.status || 'new';
      g.issues.push({ type: r.issue_type || '', detail: r.detail || '', severity: r.severity });
      if (r.result_id) g.ids.push(r.result_id);
    });
    return order.map(function(k) { return map[k]; });
  }

  function statusBadgeHtml(status) {
    status = status || 'new';
    var mod = status === 'fixed' ? 'success'
            : status === 'in_progress' ? 'info'
            : status === 'ignored' ? 'muted'
            : 'warning';
    var label = i18n['status_' + status] || status;
    return '<span class="ok-badge ok-badge-' + mod + '">' + esc(label) + '</span>';
  }

  function entityEditUrl(type, id) {
    var map = (cfg.entityEditUrls || {});
    var tpl = map[type];
    if (!tpl || !id) return '';
    return tpl.replace('__ID__', encodeURIComponent(id));
  }

  var auditTotalGroups = 0;

  function renderAuditPage(groups, page, totalGroups) {
    auditPage = page;
    auditTotalGroups = totalGroups;
    tbody.innerHTML = '';
    if (checkAll) checkAll.checked = false;
    if (bulkBar) bulkBar.classList.add('ok-hidden');
    if (!groups.length) {
      emptyMsg.classList.remove('ok-hidden');
      if (auditPager) auditPager.innerHTML = '';
      return;
    }
    emptyMsg.classList.add('ok-hidden');
    var labels = i18n.issue_labels || {};
    function uniqIssuesByType(issues) {
      var seen = Object.create(null), out = [];
      issues.forEach(function (it) {
        var key = (it.issue_type || it.type || '') + '|' + (it.severity || '');
        if (!seen[key]) { seen[key] = true; out.push(it); }
      });
      return out;
    }
    groups.forEach(function (g) {
      var icon   = sevIcon[g.severity] || '<i data-lucide="info"></i>';
      var rowCls = sevRowClass[g.severity] || '';
      if (g.status === 'fixed')   rowCls += ' ok-row-fixed';
      if (g.status === 'ignored') rowCls += ' ok-row-ignored';

      var issuesHtml = uniqIssuesByType(g.issues).map(function (issue) {
        var sev = issue.severity || 'info';
        var mod = sev === 'error' ? 'danger-soft'
                : sev === 'warning' ? 'warning-soft'
                : 'info-soft';
        var type = issue.issue_type || issue.type || '';
        var label = labels[type] || type;
        return '<span class="ok-badge ok-badge-' + mod + '" title="' + esc(issue.detail || '') + '">' + esc(label) + '</span>';
      }).join(' ');

      var editUrl = entityEditUrl(g.entity_type, g.entity_id);
      var nameHtml = editUrl
        ? '<a href="' + editUrl + '" target="_blank" class="ok-entity-link"><i data-lucide="external-link"></i>' + esc(g.entity_name || g.entity_id || '') + '</a>'
        : esc(g.entity_name || '');

      var statusSel =
        '<select class="ok-select ok-select-sm ok-audit-status-sel" data-ids="' + g.ids.join(',') + '">' +
          ['new','in_progress','fixed','ignored'].map(function (s) {
            return '<option value="' + s + '"' + (s === g.status ? ' selected' : '') + '>' + esc(i18n['status_' + s] || s) + '</option>';
          }).join('') +
        '</select>';

      var tr = document.createElement('tr');
      tr.className = rowCls;
      tr.setAttribute('data-result-ids', g.ids.join(','));
      tr.innerHTML =
        '<td><input type="checkbox" class="ok-audit-check"></td>' +
        '<td><div class="ok-stat-icon">' + icon + '</div></td>' +
        '<td>' + esc(i18n['type_' + g.entity_type] || g.entity_type || '') + '</td>' +
        '<td>' + (g.entity_id || '') + '</td>' +
        '<td>' + nameHtml + '</td>' +
        '<td class="ok-audit-issues">' + issuesHtml + '</td>' +
        '<td>' + statusSel + '</td>' +
        '<td class="ok-col-actions">' +
          '<button type="button" class="ok-btn ok-btn-icon ok-btn-sm ok-audit-delete-row" title="' + (i18n.button_delete || 'Delete') + '"><i data-lucide="trash-2"></i></button>' +
        '</td>';
      tbody.appendChild(tr);
    });
    if (window.lucide) lucide.createIcons({ nodes: tbody.querySelectorAll('[data-lucide]') });
    if (auditPager) {
      auditPager.innerHTML = buildPagerHtml(page, Math.ceil(totalGroups / currentPerPage()), 'data-audit-page');
    }
  }

  function updateSevCounts(summary) {
    if (!summary || !filterSev) return;
    var total = (summary.error || 0) + (summary.warning || 0) + (summary.info || 0);
    var cells = {
      all:     total,
      error:   summary.error   || 0,
      warning: summary.warning || 0,
      info:    summary.info    || 0,
    };
    filterSev.querySelectorAll('.ok-sev-count').forEach(function (el) {
      var k = el.getAttribute('data-count');
      el.textContent = cells[k] != null ? cells[k] : 0;
    });
  }

  function loadResults(page) {
    page = page || 1;
    var langId = filterLang ? filterLang.value : lastLangId;
    var sev    = filterSev  ? (filterSev.dataset.value || '') : '';
    var type   = filterType ? filterType.value : '';
    var url    = cfg.auditResultsUrl
               + '&language_id=' + encodeURIComponent(langId)
               + '&severity='    + encodeURIComponent(sev)
               + '&entity_type=' + encodeURIComponent(type)
               + '&page='        + page
               + '&per_page='    + currentPerPage();

    var xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    xhr.onload = function () {
      var res;
      try { res = JSON.parse(xhr.responseText); }
      catch (e) { console.error('[audit] JSON parse failed:', e, xhr.responseText.slice(0, 500)); return; }

      if (resultsPanel) resultsPanel.classList.remove('ok-hidden');
      updateSevCounts(res.summary || {});
      try {
        renderAuditPage(res.groups || [], res.page || 1, res.total_groups || 0);
      } catch (e) {
        console.error('[audit] renderAuditPage failed:', e, res);
        if (emptyMsg) {
          emptyMsg.classList.remove('ok-hidden');
          emptyMsg.textContent = 'Render error: ' + (e && e.message ? e.message : e);
        }
      }
    };
    xhr.send();
  }

  if (auditPager) {
    auditPager.addEventListener('click', function(e) {
      var btn = e.target.closest('[data-audit-page]');
      if (btn) { e.preventDefault(); loadResults(parseInt(btn.getAttribute('data-audit-page'), 10)); }
    });
  }

  window.loadAuditResults = loadResults;

  // Auto-load previous results on page load
  loadResults();

  if (checkAll) {
    checkAll.addEventListener('change', function () {
      tbody.querySelectorAll('.ok-audit-check').forEach(function(cb){ cb.checked = checkAll.checked; });
      updateBulkBar();
    });
  }

  if (tbody) {
    tbody.addEventListener('change', function(e) {
      if (e.target.classList.contains('ok-audit-check')) updateBulkBar();
      if (e.target.classList.contains('ok-audit-status-sel')) {
        var sel  = e.target;
        var ids  = sel.dataset.ids || '';
        if (!ids) return;
        var data = new URLSearchParams({ ids: ids, status: sel.value, user_token: cfg.userToken });
        var xhr  = new XMLHttpRequest();
        xhr.open('POST', cfg.auditStatusUrl, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
          try {
            var r = JSON.parse(xhr.responseText);
            if (r.success) {
              if (window.okNotify) okNotify(i18n.saved || 'Saved', 'success');
            } else if (window.okNotify) {
              okNotify(i18n.error || 'Error', 'error');
            }
          } catch(e) {}
        };
        xhr.send(data.toString());
      }
    });
    tbody.addEventListener('click', function(e) {
      var btn = e.target.closest('.ok-audit-delete-row');
      if (!btn) return;
      var tr = btn.closest('tr');
      var ids = tr ? tr.getAttribute('data-result-ids') : '';
      if (!ids) return;
      var data = new URLSearchParams({ ids: ids, user_token: cfg.userToken });
      var xhr = new XMLHttpRequest();
      xhr.open('POST', cfg.auditDeleteUrl, true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.onload = function() { try { var r = JSON.parse(xhr.responseText); if (r.success) tr.remove(); } catch(e){} updateBulkBar(); };
      xhr.send(data.toString());
    });
  }

  if (bulkDelete) {
    bulkDelete.addEventListener('click', function() {
      var ids = Array.from(tbody.querySelectorAll('.ok-audit-check:checked')).reduce(function(acc, cb) {
        var raw = cb.closest('tr').getAttribute('data-result-ids') || '';
        return acc.concat(raw.split(',').filter(Boolean));
      }, []);
      if (!ids.length) return;
      var data = new URLSearchParams({ ids: ids.join(','), user_token: cfg.userToken });
      var xhr = new XMLHttpRequest();
      xhr.open('POST', cfg.auditDeleteUrl, true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.onload = function() {
        try {
          var r = JSON.parse(xhr.responseText);
          if (r.success) {
            tbody.querySelectorAll('.ok-audit-check:checked').forEach(function(cb){ cb.closest('tr').remove(); });
            updateBulkBar();
          }
        } catch(e){}
      };
      xhr.send(data.toString());
    });
  }

  runBtn.addEventListener('click', function () {
    lastLangId = langSel ? langSel.value : '0';
    runBtn.disabled = true;
    if (runBody) runBody.classList.remove('ok-hidden');
    if (spinner) spinner.classList.remove('ok-hidden');
    if (resultsPanel) resultsPanel.classList.add('ok-hidden');

    var data = new URLSearchParams({ language_id: lastLangId, user_token: cfg.userToken });
    var xhr = new XMLHttpRequest();
    xhr.open('POST', cfg.auditRunUrl, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function () {
      runBtn.disabled = false;
      if (spinner) spinner.classList.add('ok-hidden');
      if (runBody) runBody.classList.add('ok-hidden');
      try {
        var res = JSON.parse(xhr.responseText);
        if (res.success) {
          if (filterLang && langSel) filterLang.value = langSel.value;
          loadResults(1);
        }
      } catch(e) {}
    };
    xhr.onerror = function () { runBtn.disabled = false; if (spinner) spinner.classList.add('ok-hidden'); };
    xhr.send(data.toString());
  });

  if (filterSev) {
    filterSev.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-sev]');
      if (!btn) return;
      var sev = btn.getAttribute('data-sev');
      filterSev.dataset.value = sev;
      filterSev.querySelectorAll('[data-sev]').forEach(function (b) {
        b.classList.toggle('active', b.getAttribute('data-sev') === sev);
      });
      loadResults(1);
    });
  }
  if (filterType)  filterType.addEventListener('change',  function () { loadResults(1); });
  if (filterLimit) filterLimit.addEventListener('change', function () { loadResults(1); });
  if (filterLang)  filterLang.addEventListener('change',  function () { loadResults(1); });
}());

// ── Absolute URL page ──────────────────────────────────────────────────────────
(function () {
  'use strict';
  var cfg  = window.seoCoreConfig || {};
  var i18n = window.seoCoreI18n || {};

  var scanBtn    = document.getElementById('ok-absurl-scan');
  var replaceBtn = document.getElementById('ok-absurl-replace');
  var logReload  = document.getElementById('ok-absurl-log-reload');
  var logTbody   = document.getElementById('ok-absurl-log-tbody');
  var scanResults = document.getElementById('ok-absurl-scan-results');
  var scanTbody  = document.getElementById('ok-absurl-scan-tbody');
  var checkAll   = document.getElementById('ok-absurl-check-all');

  if (!logTbody) return;

  var logPage = 1;
  var logLimit = 50;

  function loadLog() {
    var url = cfg.absurlLogUrl + '&page=' + logPage + '&limit=' + logLimit;
    var xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    xhr.onload = function () {
      try {
        var res = JSON.parse(xhr.responseText);
        logTbody.innerHTML = '';
        (res.items || []).forEach(function (r) {
          logTbody.insertAdjacentHTML('beforeend',
            '<tr><td>' + r.entity_type + '</td><td>' + r.old_url + '</td><td>' + r.new_url +
            '</td><td>' + r.rows_updated + '</td><td>' + (r.created_at || '') + '</td></tr>'
          );
        });
        if (!res.items || !res.items.length) {
          logTbody.innerHTML = '<tr><td colspan="5" class="text-center ok-muted">Записів немає</td></tr>';
        }
      } catch(e) {}
    };
    xhr.send();
  }

  if (logReload) logReload.addEventListener('click', loadLog);
  loadLog();

  if (scanBtn) {
    scanBtn.addEventListener('click', function () {
      var domain = (document.getElementById('ok-absurl-domain') || {}).value || '';
      if (!domain) return;
      scanBtn.disabled = true;
      var data = new URLSearchParams({ domain: domain, user_token: cfg.userToken });
      var xhr = new XMLHttpRequest();
      xhr.open('POST', cfg.absurlScanUrl, true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.onload = function () {
        scanBtn.disabled = false;
        try {
          var res = JSON.parse(xhr.responseText);
          if (res.success && scanTbody) {
            scanResults.classList.remove('ok-hidden');
            scanResults.querySelector('.ok-scan-count').textContent =
              (i18n.absurl_scan_found || 'found') + ': ' + res.total;
            scanTbody.innerHTML = '';
            (res.items || []).forEach(function (r) {
              scanTbody.insertAdjacentHTML('beforeend',
                '<tr data-type="' + r.entity_type + '" data-id="' + r.entity_id + '">' +
                '<td><input type="checkbox" class="ok-absurl-chk" checked></td>' +
                '<td>' + r.entity_type + '</td><td>' + r.entity_id + '</td>' +
                '<td>' + r.field + '</td><td>' + r.count + '</td></tr>'
              );
            });
          }
        } catch(e) {}
      };
      xhr.onerror = function () { scanBtn.disabled = false; };
      xhr.send(data.toString());
    });
  }

  if (checkAll) {
    checkAll.addEventListener('change', function () {
      document.querySelectorAll('.ok-absurl-chk').forEach(function(c){ c.checked = checkAll.checked; });
    });
  }

  if (replaceBtn) {
    replaceBtn.addEventListener('click', function () {
      var oldD = (document.getElementById('ok-absurl-old') || {}).value || '';
      var newD = (document.getElementById('ok-absurl-new') || {}).value || '';
      var type = (document.getElementById('ok-absurl-type') || {}).value || 'product';
      var httpsOnly = (document.getElementById('ok-absurl-https-only') || {}).checked ? '1' : '0';

      if (!oldD) return;
      if (!confirm(i18n.confirm_replace_absurl || 'Replace?')) return;

      var selectedIds = [];
      document.querySelectorAll('.ok-absurl-chk:checked').forEach(function(chk){
        var tr = chk.closest('tr');
        if (tr) selectedIds.push(tr.getAttribute('data-id'));
      });

      var params = {
        old_domain:  oldD,
        new_domain:  newD,
        entity_type: type,
        https_only:  httpsOnly,
        user_token:  cfg.userToken,
      };
      selectedIds.forEach(function(id){ params['entity_ids[]'] = id; });

      replaceBtn.disabled = true;
      var data = new URLSearchParams(params);
      var xhr = new XMLHttpRequest();
      xhr.open('POST', cfg.absurlReplaceUrl, true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.onload = function () {
        replaceBtn.disabled = false;
        try {
          var res = JSON.parse(xhr.responseText);
          if (res.success) {
            window.okNotify((i18n.absurl_replaced || 'Updated') + ': ' + res.updated, 'success');
            loadLog();
          } else {
            window.okNotify(res.message || 'Error', 'error');
          }
        } catch(e) {}
      };
      xhr.onerror = function () { replaceBtn.disabled = false; };
      xhr.send(data.toString());
    });
  }
}());

// ── Header rules page ──────────────────────────────────────────────────────────
(function () {
  'use strict';
  var cfg  = window.seoCoreConfig || {};
  var i18n = window.seoCoreI18n || {};

  var addBtn   = document.getElementById('ok-hdr-add');
  var tbody    = document.getElementById('ok-hdr-tbody');
  var modal    = document.getElementById('ok-hdr-modal');
  var testBtn  = document.getElementById('ok-hdr-test-btn');

  if (!tbody) return;

  function loadRules() {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', cfg.headersListUrl, true);
    xhr.onload = function () {
      try {
        var res = JSON.parse(xhr.responseText);
        tbody.innerHTML = '';
        var items = res.items || [];
        if (!items.length) {
          tbody.innerHTML = '<tr><td colspan="8" class="text-center ok-muted">' +
            (i18n.text_no_headers_rules || '—') + '</td></tr>';
        } else {
          items.forEach(renderRow);
        }
        if (typeof lucide !== 'undefined') lucide.createIcons();
      } catch(e) {}
    };
    xhr.send();
  }

  function renderRow(r) {
    tbody.insertAdjacentHTML('beforeend',
      '<tr data-id="' + r.rule_id + '">' +
      '<td>' + (r.url_pattern || '') + '</td>' +
      '<td>' + (r.robots_value || '') + '</td>' +
      '<td>' + (r.apply_header ? '✓' : '') + '</td>' +
      '<td>' + (r.apply_meta  ? '✓' : '') + '</td>' +
      '<td>' + (r.sort_order  || 0) + '</td>' +
      '<td>' + (r.comment     || '') + '</td>' +
      '<td><span class="ok-badge ' + (r.status ? 'ok-badge-success' : 'ok-badge-muted') + '">' +
        (r.status ? 'ON' : 'OFF') + '</span></td>' +
      '<td>' +
        '<button type="button" class="ok-btn ok-btn-sm ok-btn-secondary ok-hdr-edit" data-rule=\'' + JSON.stringify(r) + '\'>' +
          '<i data-lucide="edit-2"></i></button> ' +
        '<button type="button" class="ok-btn ok-btn-sm ok-btn-danger ok-hdr-delete">' +
          '<i data-lucide="trash-2"></i></button>' +
      '</td></tr>'
    );
  }

  function openModal(rule) {
    rule = rule || {};
    document.getElementById('ok-hdr-edit-id').value      = rule.rule_id      || '0';
    document.getElementById('ok-hdr-edit-pattern').value = rule.url_pattern  || '';
    document.getElementById('ok-hdr-edit-robots').value  = rule.robots_value || '';
    document.getElementById('ok-hdr-edit-header').checked = rule.apply_header !== '0' && rule.apply_header !== 0;
    document.getElementById('ok-hdr-edit-meta').checked   = rule.apply_meta   !== '0' && rule.apply_meta   !== 0;
    document.getElementById('ok-hdr-edit-order').value   = rule.sort_order   || '0';
    document.getElementById('ok-hdr-edit-comment').value = rule.comment      || '';
    document.getElementById('ok-hdr-edit-status').checked = !rule.rule_id || !!parseInt(rule.status || 1);
    modal.classList.remove('ok-hidden');
  }

  if (addBtn) addBtn.addEventListener('click', function () { openModal(); });

  document.addEventListener('click', function (e) {
    if (e.target.closest('.ok-hdr-edit')) {
      try { openModal(JSON.parse(e.target.closest('.ok-hdr-edit').getAttribute('data-rule'))); } catch(ex) {}
    }
    if (e.target.closest('.ok-hdr-delete')) {
      if (!confirm(i18n.confirm_delete_header || 'Delete?')) return;
      var id = e.target.closest('tr').getAttribute('data-id');
      var data = new URLSearchParams({ rule_id: id, user_token: cfg.userToken });
      var xhr = new XMLHttpRequest();
      xhr.open('POST', cfg.headerDeleteUrl, true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.onload = function () { loadRules(); };
      xhr.send(data.toString());
    }
    if (e.target.closest('#ok-hdr-modal-close') || e.target.closest('#ok-hdr-modal-cancel')) {
      modal.classList.add('ok-hidden');
    }
    if (e.target.closest('#ok-hdr-modal-save')) {
      var params = {
        rule_id:      document.getElementById('ok-hdr-edit-id').value,
        url_pattern:  document.getElementById('ok-hdr-edit-pattern').value,
        robots_value: document.getElementById('ok-hdr-edit-robots').value,
        apply_header: document.getElementById('ok-hdr-edit-header').checked ? '1' : '0',
        apply_meta:   document.getElementById('ok-hdr-edit-meta').checked   ? '1' : '0',
        sort_order:   document.getElementById('ok-hdr-edit-order').value,
        comment:      document.getElementById('ok-hdr-edit-comment').value,
        status:       document.getElementById('ok-hdr-edit-status').checked ? '1' : '0',
        user_token:   cfg.userToken,
      };
      var data = new URLSearchParams(params);
      var xhr = new XMLHttpRequest();
      xhr.open('POST', cfg.headerSaveUrl, true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.onload = function () {
        modal.classList.add('ok-hidden');
        loadRules();
      };
      xhr.send(data.toString());
    }
  });

  if (testBtn) {
    testBtn.addEventListener('click', function () {
      var uri = (document.getElementById('ok-hdr-test-uri') || {}).value || '';
      var resultEl = document.getElementById('ok-hdr-test-result');
      var data = new URLSearchParams({ uri: uri, user_token: cfg.userToken });
      var xhr = new XMLHttpRequest();
      xhr.open('POST', cfg.headerTestUrl, true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.onload = function () {
        try {
          var res = JSON.parse(xhr.responseText);
          resultEl.classList.remove('ok-hidden');
          if (res.match) {
            resultEl.innerHTML = '<span class="ok-badge ok-badge-success">' + (i18n.hdr_match || 'Match:') + ' ' +
              res.match.url_pattern + ' → ' + res.match.robots_value + '</span>';
          } else {
            resultEl.innerHTML = '<span class="ok-badge ok-badge-muted">' + (i18n.hdr_no_match || 'No match') + '</span>';
          }
        } catch(e) {}
      };
      xhr.send(data.toString());
    });
  }

  loadRules();
}());

// ── robots.txt page ────────────────────────────────────────────────────────────
(function () {
  'use strict';
  var cfg  = window.seoCoreConfig || {};
  var i18n = window.seoCoreI18n || {};

  var saveBtn  = document.getElementById('ok-robots-save');
  var textarea = document.getElementById('ok-robots-content');
  var errSpan  = document.getElementById('ok-robots-errors');

  if (!saveBtn) return;

  saveBtn.addEventListener('click', function () {
    var content = textarea ? textarea.value : '';
    saveBtn.disabled = true;
    if (errSpan) errSpan.classList.add('ok-hidden');

    var data = new URLSearchParams({ content: content, user_token: cfg.userToken });
    var xhr = new XMLHttpRequest();
    xhr.open('POST', cfg.robotsSaveUrl, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function () {
      saveBtn.disabled = false;
      try {
        var res = JSON.parse(xhr.responseText);
        if (res.success) {
          window.okNotify(i18n.robots_saved || 'Saved', 'success');
        } else {
          if (errSpan) { errSpan.textContent = res.message || 'Error'; errSpan.classList.remove('ok-hidden'); }
        }
      } catch(e) {}
    };
    xhr.onerror = function () { saveBtn.disabled = false; };
    xhr.send(data.toString());
  });

  document.addEventListener('click', function (e) {
    if (!e.target.closest('.ok-robots-restore')) return;
    if (!confirm(i18n.confirm_restore_robots || 'Restore?')) return;
    var path = e.target.closest('tr').getAttribute('data-path') || '';
    var data = new URLSearchParams({ backup_path: path, user_token: cfg.userToken });
    var xhr = new XMLHttpRequest();
    xhr.open('POST', cfg.robotsRestoreUrl, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function () {
      try {
        var res = JSON.parse(xhr.responseText);
        if (res.success && textarea) {
          textarea.value = res.content || '';
          window.okNotify(i18n.robots_saved || 'Restored', 'success');
        } else {
          window.okNotify(res.message || 'Error', 'error');
        }
      } catch(e) {}
    };
    xhr.send(data.toString());
  });

  // ── Diff modal (LCS-based line diff) ─────────────────────────────────────
  function renderDiff(backupText, currentText) {
    var a = (backupText  || '').split(/\r?\n/);
    var b = (currentText || '').split(/\r?\n/);
    var n = a.length, m = b.length;

    // LCS length matrix
    var dp = new Array(n + 1);
    for (var i = 0; i <= n; i++) dp[i] = new Int32Array(m + 1);
    for (var i = n - 1; i >= 0; i--) {
      for (var j = m - 1; j >= 0; j--) {
        dp[i][j] = a[i] === b[j]
          ? dp[i + 1][j + 1] + 1
          : Math.max(dp[i + 1][j], dp[i][j + 1]);
      }
    }

    // Backtrack → interleaved diff ops
    var ops = [];
    var i = 0, j = 0;
    while (i < n && j < m) {
      if (a[i] === b[j]) {
        ops.push({ t: ' ', s: a[i] }); i++; j++;
      } else if (dp[i + 1][j] >= dp[i][j + 1]) {
        ops.push({ t: '-', s: a[i] }); i++;
      } else {
        ops.push({ t: '+', s: b[j] }); j++;
      }
    }
    while (i < n) { ops.push({ t: '-', s: a[i++] }); }
    while (j < m) { ops.push({ t: '+', s: b[j++] }); }

    var changed = ops.some(function (o) { return o.t !== ' '; });
    if (!changed) {
      return '<div class="ok-muted">' + esc(i18n.text_no_diff || '') + '</div>';
    }
    return ops.map(function (r) {
      var cls = r.t === '-' ? 'ok-diff-line ok-diff-rem'
              : r.t === '+' ? 'ok-diff-line ok-diff-add'
              : 'ok-diff-line';
      return '<span class="' + cls + '"><span class="ok-diff-sign">' + r.t + '</span>' + esc(r.s) + '</span>';
    }).join('');
  }

  function openDiffModal() {
    var m = document.getElementById('ok-robots-diff-modal');
    if (m) m.classList.remove('ok-hidden');
  }
  function closeDiffModal() {
    var m = document.getElementById('ok-robots-diff-modal');
    if (m) m.classList.add('ok-hidden');
  }

  document.addEventListener('click', function (e) {
    if (e.target.closest('.ok-robots-diff')) {
      var path = e.target.closest('tr').getAttribute('data-path') || '';
      var titleEl = document.getElementById('ok-robots-diff-title');
      var bodyEl  = document.getElementById('ok-robots-diff-body');
      if (bodyEl) bodyEl.innerHTML = '<div class="ok-muted">…</div>';
      openDiffModal();

      var data = new URLSearchParams({ backup_path: path, user_token: cfg.userToken });
      var xhr = new XMLHttpRequest();
      xhr.open('POST', cfg.robotsDiffUrl, true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.onload = function () {
        try {
          var res = JSON.parse(xhr.responseText);
          if (!res.success) { if (bodyEl) bodyEl.textContent = res.message || 'Error'; return; }
          if (titleEl) titleEl.innerHTML = '<i data-lucide="file-diff"></i> ' + esc(res.filename || '');
          if (bodyEl)  bodyEl.innerHTML = renderDiff(res.backup, res.current);
          if (window.lucide) lucide.createIcons({ nodes: document.querySelectorAll('#ok-robots-diff-modal [data-lucide]') });
        } catch(e) {}
      };
      xhr.send(data.toString());
      return;
    }
    if (e.target.closest('#ok-robots-diff-close') || e.target.closest('#ok-robots-diff-cancel')) {
      closeDiffModal();
      return;
    }
    if (e.target.id === 'ok-robots-diff-modal') {
      closeDiffModal();
    }
  });
}());

// ── Sitemap page ───────────────────────────────────────────────────────────────
(function () {
  'use strict';
  var cfg  = window.seoCoreConfig || {};
  var i18n = window.seoCoreI18n || {};

  var refreshBtn  = document.getElementById('ok-sm-refresh');
  var generateBtn = document.getElementById('ok-sm-generate');
  var genResult   = document.getElementById('ok-sm-generate-result');

  if (!document.getElementById('ok-sm-status-body')) return;

  if (refreshBtn) {
    refreshBtn.addEventListener('click', function () {
      refreshBtn.disabled = true;
      var xhr = new XMLHttpRequest();
      xhr.open('GET', cfg.sitemapStatusUrl, true);
      xhr.onload = function () {
        refreshBtn.disabled = false;
        try {
          var res = JSON.parse(xhr.responseText);
          if (res.success && res.status) {
            window.location.reload();
          }
        } catch(e) {}
      };
      xhr.onerror = function () { refreshBtn.disabled = false; };
      xhr.send();
    });
  }

  if (generateBtn) {
    generateBtn.addEventListener('click', function () {
      generateBtn.disabled = true;
      var data = new URLSearchParams({ user_token: cfg.userToken });
      var xhr = new XMLHttpRequest();
      xhr.open('POST', cfg.sitemapGenerateUrl, true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.onload = function () {
        generateBtn.disabled = false;
        try {
          var res = JSON.parse(xhr.responseText);
          if (genResult) {
            genResult.classList.remove('ok-hidden');
            genResult.innerHTML = res.success
              ? '<span class="ok-badge ok-badge-success">' + (i18n.sm_generate_ok   || 'Triggered') + '</span>'
              : '<span class="ok-badge ok-badge-error">'   + (i18n.sm_generate_fail || 'Failed')    + '</span>';
          }
        } catch(e) {}
      };
      xhr.onerror = function () { generateBtn.disabled = false; };
      xhr.send(data.toString());
    });
  }

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.ok-sm-ping');
    if (!btn) return;
    var sitemapUrl = btn.getAttribute('data-url') || '';
    var engine     = btn.getAttribute('data-engine') || 'google';
    var data = new URLSearchParams({ sitemap_url: sitemapUrl, engine: engine, user_token: cfg.userToken });
    btn.disabled = true;
    var xhr = new XMLHttpRequest();
    xhr.open('POST', cfg.sitemapPingUrl, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function () {
      btn.disabled = false;
      try {
        var res = JSON.parse(xhr.responseText);
        var level = res.success ? 'success' : (res.deprecated ? 'warning' : 'error');
        var msg   = res.message || (res.success ? (i18n.sm_ping_ok || 'Pinged') : (i18n.sm_ping_fail || 'Ping failed'));
        window.okNotify(msg, level);
      } catch(e) {}
    };
    xhr.onerror = function () { btn.disabled = false; };
    xhr.send(data.toString());
  });
}());

// Radio-card active state toggle
document.addEventListener('change', function (e) {
  var inp = e.target;
  if (!inp || inp.type !== 'radio' || !inp.closest('.ok-radio-card')) return;
  var name = inp.name;
  document.querySelectorAll('.ok-radio-card input[type="radio"][name="' + name + '"]').forEach(function (r) {
    r.closest('.ok-radio-card').classList.toggle('active', r.checked);
  });
});

}); // DOMContentLoaded
