/**
 * EasyCheckout — Integration field types (catalog-side renderers)
 * © 2026 oc-kit.com | https://oc-kit.com
 *
 * Реєструє типи полів, які надають enabled-інтеграції (np_city, np_warehouse,
 * up_region, up_district, up_city, up_postoffice). Self-mount по селектору
 * [data-okec-integration-field] — працює і без OkEasyCheckout core (як fallback).
 */
(function () {
  'use strict';

  var BASE_URL  = (window.OkecConfig && window.OkecConfig.urls && window.OkecConfig.urls.api_search) || '';
  var DEBOUNCE  = 250;
  var MIN_QUERY = 2;

  if (!BASE_URL) {
    // Тихий fallback — без endpoint поля будуть звичайними text-інпутами
    return;
  }

  var TYPE_MAP = {
    np_city:        { integration: 'nova_poshta', searchType: 'city',       parentField: null },
    np_warehouse:   { integration: 'nova_poshta', searchType: 'warehouse',  parentField: 'np_city' },
    up_region:      { integration: 'ukrposhta',   searchType: 'region',     parentField: null },
    up_district:    { integration: 'ukrposhta',   searchType: 'district',   parentField: 'up_region' },
    up_city:        { integration: 'ukrposhta',   searchType: 'city',       parentField: 'up_district' },
    up_postoffice:  { integration: 'ukrposhta',   searchType: 'postoffice', parentField: 'up_city' },
  };

  function debounce(fn, ms) {
    var t = null;
    return function () {
      var args = arguments, ctx = this;
      clearTimeout(t);
      t = setTimeout(function () { fn.apply(ctx, args); }, ms);
    };
  }

  function buildUrl(meta, q, parentValue, page) {
    var u = BASE_URL
          + (BASE_URL.indexOf('?') === -1 ? '?' : '&')
          + 'integration=' + encodeURIComponent(meta.integration)
          + '&type='       + encodeURIComponent(meta.searchType)
          + '&q='          + encodeURIComponent(q || '')
          + '&page='       + (page || 1)
          + '&limit=50';
    if (parentValue) u += '&parent=' + encodeURIComponent(parentValue);
    return u;
  }

  function findParentValue(input, parentField) {
    if (!parentField) return '';
    var form = input.closest('form, .okec-form, body') || document;
    var hidden = form.querySelector('[data-okec-integration-field][data-okec-field="' + parentField + '"] input.okec-int-id');
    return hidden ? (hidden.value || '') : '';
  }

  function appendLoadMore(dropdown, wrap, meta) {
    var btn = document.createElement('div');
    btn.className = 'okec-int-loadmore';
    btn.textContent = '↓';
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      var nextPage = parseInt(wrap.dataset.page || '1', 10) + 1;
      var q = wrap.dataset.lastQ || '';
      var parentVal = findParentValue(wrap, meta.parentField);
      btn.remove();
      fetch(buildUrl(meta, q, parentVal, nextPage), { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          var items = (data && data.items) || [];
          items.forEach(function (it) { dropdown.appendChild(makeRow(it)); });
          if (data && data.has_more) appendLoadMore(dropdown, wrap, meta);
          wrap.dataset.page = String(nextPage);
        });
    });
    dropdown.appendChild(btn);
  }

  function makeRow(item) {
    var div = document.createElement('div');
    div.className = 'okec-int-option';
    div.dataset.id = item.id;
    div.dataset.label = item.label;
    div.textContent = item.label;
    return div;
  }

  function mountInput(wrap) {
    var type = wrap.getAttribute('data-okec-field');
    var meta = TYPE_MAP[type];
    if (!meta) return;

    var input = wrap.querySelector('input.okec-int-search');
    var hidden = wrap.querySelector('input.okec-int-id');
    var dropdown = wrap.querySelector('.okec-int-dropdown');
    if (!input || !hidden || !dropdown) return;

    var doSearch = debounce(function () {
      var q = input.value.trim();
      var parentVal = findParentValue(wrap, meta.parentField);
      if (meta.parentField && !parentVal) {
        dropdown.innerHTML = '<div class="okec-int-empty" data-msg="parent">…</div>';
        dropdown.classList.add('open');
        return;
      }
      if (!parentVal && q.length < MIN_QUERY) {
        dropdown.classList.remove('open');
        return;
      }
      wrap.dataset.page = '1';
      wrap.dataset.lastQ = q;
      fetch(buildUrl(meta, q, parentVal, 1), { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          dropdown.innerHTML = '';
          var items = (data && data.items) || [];
          if (!items.length) {
            dropdown.innerHTML = '<div class="okec-int-empty">—</div>';
          } else {
            items.forEach(function (it) { dropdown.appendChild(makeRow(it)); });
            if (data && data.has_more) appendLoadMore(dropdown, wrap, meta);
          }
          dropdown.classList.add('open');
        })
        .catch(function () { dropdown.classList.remove('open'); });
    }, DEBOUNCE);

    input.addEventListener('input', function () { hidden.value = ''; doSearch(); });
    input.addEventListener('focus', doSearch);
    document.addEventListener('click', function (e) {
      if (!wrap.contains(e.target)) dropdown.classList.remove('open');
    });

    dropdown.addEventListener('click', function (e) {
      var opt = e.target.closest('.okec-int-option');
      if (!opt) return;
      hidden.value = opt.dataset.id;
      input.value  = opt.dataset.label;
      dropdown.classList.remove('open');
      // Notify dependent fields to reset
      var ev = new CustomEvent('okec:integrationField', { detail: { type: type, id: opt.dataset.id } });
      document.dispatchEvent(ev);
    });

    // Reset child fields when this changes (cascading)
    document.addEventListener('okec:integrationField', function (ev) {
      if (!ev.detail) return;
      if (meta.parentField === ev.detail.type) {
        hidden.value = '';
        input.value = '';
        dropdown.innerHTML = '';
      }
    });
  }

  function mountAll(scope) {
    (scope || document).querySelectorAll('[data-okec-integration-field]').forEach(mountInput);
  }

  // Self-mount on DOMReady + react to dynamic block reloads
  if (document.readyState !== 'loading') mountAll(document);
  else document.addEventListener('DOMContentLoaded', function () { mountAll(document); });

  document.addEventListener('okec:afterReload', function () { mountAll(document); });

  // Optional: register with OkEasyCheckout core (no-op if API not loaded)
  if (window.OkEasyCheckout && typeof window.OkEasyCheckout.registerFieldType === 'function') {
    Object.keys(TYPE_MAP).forEach(function (type) {
      window.OkEasyCheckout.registerFieldType(type, {
        mount: function (el) { mountInput(el); },
      });
    });
  }
}());
