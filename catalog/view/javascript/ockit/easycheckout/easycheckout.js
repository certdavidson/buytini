/**
 * EasyCheckout — catalog frontend (vanilla IIFE)
 * © 2026 oc-kit.com | https://oc-kit.com
 *
 * Stage 4.4:
 *  - Завантаження shipping/payment-методів через AJAX (loadMethods)
 *  - Country → Zone каскад
 *  - Reload-on-change debounced trigger of loadMethods
 *  - Submit/confirm flow → redirect to payment route
 *  - IMask + cart actions з 4.3
 */
(function () {
  'use strict';

  var cfg  = window.OkecConfig || { urls: {}, text: {} };
  var URLS = cfg.urls || {};
  var TEXT = cfg.text || {};

  // ─── Public API + pub/sub ────────────────────────────────────────────────
  var listeners = {};
  var api = {
    on:  function (event, cb) { (listeners[event] || (listeners[event] = [])).push(cb); return api; },
    once: function (event, cb) {
      var wrap = function (p) { api.off(event, wrap); cb(p); };
      return api.on(event, wrap);
    },
    off: function (event, cb) {
      var arr = listeners[event]; if (!arr) return api;
      var idx = arr.indexOf(cb); if (idx >= 0) arr.splice(idx, 1); return api;
    },
    trigger: function (event, payload) {
      (listeners[event] || []).forEach(function (cb) {
        try { cb(payload); } catch (e) { /* keep-going */ }
      });
      return api;
    },
    getState: function () {
      var root = document.querySelector('.okec-checkout');
      if (!root) return {};
      var state = {};
      root.querySelectorAll('[name^="okec["]').forEach(function (el) {
        var m = el.name.match(/^okec\[(.+)\]$/); if (!m) return;
        var key = m[1];
        if (el.type === 'checkbox' || el.type === 'radio') {
          if (el.checked) state[key] = el.value;
          else if (state[key] === undefined) state[key] = '';
        } else { state[key] = el.value; }
      });
      return state;
    },
    getField: function (code) {
      var el = document.querySelector('[name="okec[' + code + ']"]');
      if (!el) return null;
      if (el.type === 'checkbox' || el.type === 'radio') return el.checked ? el.value : '';
      return el.value;
    },
    setField: function (code, value) {
      var el = document.querySelector('[name="okec[' + code + ']"]');
      if (!el) return;
      if (el.type === 'checkbox') el.checked = !!value;
      else if (el.type === 'radio') {
        document.querySelectorAll('[name="okec[' + code + ']"]').forEach(function (r) {
          r.checked = (r.value === value);
        });
      } else { el.value = value == null ? '' : String(value); }
      el.dispatchEvent(new Event('change', { bubbles: true }));
    },
    submit: function () { triggerSubmit(); },
    emit:   function (event, payload) { return api.trigger(event, payload); },
    setStep: function (stepId) { api.trigger('okec:stepChange', { to: stepId }); },
    reloadBlocks: function (blockIds) {
      if (!URLS.reload_blocks) return Promise.resolve({ blocks: {}, state: api.getState() });
      api.trigger('okec:beforeReload', { blockIds: blockIds || [], postedFields: api.getState() });
      var body = serializeForm({ okec: api.getState(), affected_blocks: (blockIds || []) });
      return fetch(URLS.reload_blocks, {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body,
      }).then(function (r) { return r.json(); }).then(function (data) {
        // Server-rendered HTML for each block → swap into DOM
        if (data && data.success && data.blocks && typeof data.blocks === 'object') {
          Object.keys(data.blocks).forEach(function (blockId) {
            var html = data.blocks[blockId];
            if (typeof html !== 'string' || !html) return;
            var el = document.querySelector('[data-block-id="' + blockId + '"]');
            if (!el) return;
            // Replace innerHTML — preserves outer wrapper з data-attrs
            el.innerHTML = html;
          });
          // Re-bind dynamic features (masks, autocomplete, etc.)
          if (typeof bindMasks === 'function')           bindMasks(document);
          if (typeof bindCountryCascade === 'function')  bindCountryCascade(document);
          if (typeof bindConditionalFields === 'function') bindConditionalFields(document);
        }
        api.trigger('okec:afterReload', { blockIds: blockIds || [], data: data });
        return data;
      });
    },
    validateField: function ()   { return Promise.resolve({ valid: true }); },
    validateAll:   function ()   { return Promise.resolve({ valid: true }); },
    registerFieldType: function (type, descriptor) { (api._fieldTypes = api._fieldTypes || {})[type] = descriptor; },
    registerBlockType: function (type, descriptor) { (api._blockTypes = api._blockTypes || {})[type] = descriptor; },
  };
  window.OkEasyCheckout = api;

  // ─── Toast notifications (catalog-side, mirroring admin okNotify) ──────
  function notify(msg, type) {
    if (!msg) return;
    var box = document.getElementById('okec-toast-host');
    if (!box) {
      box = document.createElement('div');
      box.id = 'okec-toast-host';
      document.body.appendChild(box);
    }
    var t = document.createElement('div');
    t.className = 'okec-toast okec-toast--' + (type || 'info');
    t.textContent = msg;
    box.appendChild(t);
    requestAnimationFrame(function () { t.classList.add('is-shown'); });
    setTimeout(function () {
      t.classList.remove('is-shown');
      setTimeout(function () { t.remove(); }, 250);
    }, 4000);
  }
  api.notify = notify;

  // ─── DOM ready ──────────────────────────────────────────────────────────
  function ready(fn) {
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn);
    else fn();
  }

  // ─── HTTP helpers ───────────────────────────────────────────────────────
  function postForm(url, body) {
    return fetch(url, {
      method:  'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body:    typeof body === 'string' ? body : new URLSearchParams(body || {}),
    }).then(function (r) { return r.text(); });
  }
  function postJson(url, body) {
    return fetch(url, {
      method:  'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body:    serializeForm(body || {}),
    }).then(function (r) { return r.json(); });
  }
  function getJson(url) {
    return fetch(url, { credentials: 'same-origin' }).then(function (r) { return r.json(); });
  }
  /** Сериалізує { okec: {...}, shipping_method: '...' } у URLSearchParams з підтримкою nested-keys. */
  function serializeForm(obj) {
    var p = new URLSearchParams();
    Object.keys(obj).forEach(function (k) {
      var v = obj[k];
      if (v === null || v === undefined) return;
      if (typeof v === 'object' && !Array.isArray(v)) {
        Object.keys(v).forEach(function (sk) { p.append(k + '[' + sk + ']', v[sk] == null ? '' : String(v[sk])); });
      } else { p.append(k, String(v)); }
    });
    return p;
  }

  // ─── IMask binding ──────────────────────────────────────────────────────
  function bindMasks(scope) {
    if (!window.IMask) return;
    (scope || document).querySelectorAll('input[data-okec-mask]').forEach(function (el) {
      if (el._imask) return;
      var pattern = (el.getAttribute('data-okec-mask') || '').trim();
      if (!pattern) return;
      try {
        var maskOpts;
        if (/^[\s+\-_().,/0-9aA*?#]+$/.test(pattern)) {
          maskOpts = { mask: pattern.replace(/9/g, '0') };
        } else {
          var re = pattern.replace(/^\/|\/[gimsu]*$/g, '');
          maskOpts = { mask: new RegExp(re) };
        }
        el._imask = window.IMask(el, maskOpts);
      } catch (e) { /* ignore */ }
    });
  }

  // ─── Cart ───────────────────────────────────────────────────────────────
  function lockCartItem(itemEl) {
    if (!itemEl) return function () {};
    itemEl.classList.add('okec-block-cart__item--loading');
    return function () { itemEl.classList.remove('okec-block-cart__item--loading'); };
  }
  function handleCartAction(btn) {
    var action = btn.getAttribute('data-okec-action');
    var cartId = btn.getAttribute('data-cart-id');
    var item   = btn.closest('.okec-block-cart__item');
    if (!cartId) return;
    var unlock = lockCartItem(item);
    var promise;
    if (action === 'cart-remove') {
      promise = postForm(URLS.cart_remove, { key: cartId });
    } else if (action === 'qty-increment' || action === 'qty-decrement') {
      var numEl = item ? item.querySelector('.okec-block-cart__qty-num') : null;
      if (!numEl) { unlock(); return; }
      var delta = action === 'qty-increment' ? 1 : -1;
      var cur = parseInt(numEl.textContent, 10) || 1;
      var min = parseInt(numEl.getAttribute('data-min'), 10) || 1;
      var next = Math.max(min, cur + delta);
      if (next === cur) { unlock(); return; }
      numEl.textContent = next;
      promise = postForm(URLS.cart_edit, { key: cartId, quantity: next });
    } else { unlock(); return; }
    promise
      .then(function () {
        api.trigger('okec:cartUpdated', { cartId: cartId, action: action });
        refreshCartState(unlock);
      })
      .catch(function () { unlock(); notify(TEXT.cart_failed, 'error'); });
  }

  /** AJAX cart-block update без window.reload + re-fetch methods/totals. */
  function refreshCartState(unlock) {
    if (!URLS.cart_state) {
      window.location.reload();
      return;
    }
    fetch(URLS.cart_state, { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (unlock) unlock();
        if (!data || !data.success) { window.location.reload(); return; }
        if (data.empty) {
          window.location.href = 'index.php?route=checkout/cart';
          return;
        }
        renderCartProducts(data.cart_products || []);
        renderSummary(data.totals || []);
        updateSubmitAmount(data.totals || []);
        scheduleLoadMethods();
      })
      .catch(function () {
        if (unlock) unlock();
        window.location.reload();
      });
  }

  var QTY_MINUS_SVG = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/></svg>';
  var QTY_PLUS_SVG  = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>';

  function renderCartProducts(products) {
    var list = document.querySelector('.okec-block-cart__list');
    if (!list) return;
    var html = '';
    products.forEach(function (p) {
      var cid  = escapeAttr(p.cart_id);
      var opts = [];
      if (p.model) opts.push(p.model);
      (p.options || []).forEach(function (o) { if (o && o.value) opts.push(o.value); });

      html += '<li class="okec-block-cart__item">'
           + (p.thumb ? '<a href="' + escapeAttr(p.href) + '" class="okec-block-cart__thumb"><img src="' + escapeAttr(p.thumb) + '" alt=""></a>' : '')
           + '<div class="okec-block-cart__info">'
           +   '<a href="' + escapeAttr(p.href) + '" class="okec-block-cart__name">' + escapeHtml(p.name) + '</a>'
           +   (opts.length ? '<div class="okec-block-cart__opts">' + escapeHtml(opts.join(' · ')) + '</div>' : '')
           +   '<div class="okec-block-cart__qty">'
           +     '<button type="button" class="okec-block-cart__qty-btn" data-okec-action="qty-decrement" data-cart-id="' + cid + '" aria-label="−">' + QTY_MINUS_SVG + '</button>'
           +     '<span class="okec-block-cart__qty-num" data-min="' + (p.minimum || 1) + '">' + (p.quantity || 1) + '</span>'
           +     '<button type="button" class="okec-block-cart__qty-btn" data-okec-action="qty-increment" data-cart-id="' + cid + '" aria-label="+">' + QTY_PLUS_SVG + '</button>'
           +   '</div>'
           + '</div>'
           + '<div class="okec-block-cart__total">' + escapeHtml(p.total) + '</div>'
           + '<button type="button" class="okec-block-cart__remove" data-okec-action="cart-remove" data-cart-id="' + cid + '" aria-label="×">×</button>'
           + '</li>';
    });
    list.innerHTML = html;
  }

  // ─── Methods (shipping/payment) ─────────────────────────────────────────
  var loadMethodsTimer = null;
  var loadMethodsInflight = null;
  var latestMethodsState = { has_shipping: false, shipping_count: 0, payment_count: 0 };

  /** Disable submit якщо shipping required але немає options, або немає payment-методів. */
  function updateSubmitGate() {
    var btn = document.querySelector('[data-okec-action="confirm"]');
    if (!btn) return;
    var s = latestMethodsState;
    var blocked = (s.has_shipping && s.shipping_count === 0) || (s.payment_count === 0);
    if (blocked) {
      btn.classList.add('okec-btn--gated');
      btn.setAttribute('aria-disabled', 'true');
    } else {
      btn.classList.remove('okec-btn--gated');
      btn.removeAttribute('aria-disabled');
    }
  }

  function scheduleLoadMethods() {
    clearTimeout(loadMethodsTimer);
    loadMethodsTimer = setTimeout(loadMethods, 350);
  }

  function loadMethods() {
    var state = api.getState();
    var shippingBox = document.querySelector('[data-okec-methods="shipping"]');
    var paymentBox  = document.querySelector('[data-okec-methods="payment"]');
    if (shippingBox) shippingBox.classList.add('okec-methods--loading');
    if (paymentBox)  paymentBox.classList.add('okec-methods--loading');

    var shippingMethod = (document.querySelector('input[name="shipping_method"]:checked') || {}).value || '';
    var paymentMethod  = (document.querySelector('input[name="payment_method"]:checked') || {}).value  || '';

    loadMethodsInflight = postJson(URLS.load_methods, {
      okec:            state,
      shipping_method: shippingMethod,
      payment_method:  paymentMethod,
    })
      .then(function (data) {
        loadMethodsInflight = null;   // settled — інакше triggerSubmit() зациклиться на .then
        if (shippingBox) shippingBox.classList.remove('okec-methods--loading');
        if (paymentBox)  paymentBox.classList.remove('okec-methods--loading');
        if (!data || !data.success) return;
        if (shippingBox) renderShipping(shippingBox, data.shipping_methods, data.has_shipping);
        if (paymentBox)  renderPayment(paymentBox, data.payment_methods);
        renderSummary(data.totals || []);
        updateSubmitAmount(data.totals || []);
        // Кешуємо стан для гейтингу submit
        latestMethodsState = {
          has_shipping:    !!data.has_shipping,
          shipping_count:  (data.shipping_methods || []).reduce(function (n, m) { return n + ((m.options || []).length); }, 0),
          payment_count:   (data.payment_methods || []).length,
        };
        updateSubmitGate();
        api.trigger('okec:methodsLoaded', data);
      })
      .catch(function () {
        loadMethodsInflight = null;
        if (shippingBox) shippingBox.classList.remove('okec-methods--loading');
        if (paymentBox)  paymentBox.classList.remove('okec-methods--loading');
      });
  }

  function renderShipping(box, methods, hasShipping) {
    var prevSelected = (box.querySelector('input[type="radio"]:checked') || {}).value || '';
    box.innerHTML = '';
    if (!hasShipping) {
      box.classList.add('okec-methods--hidden');
      return;
    }
    box.classList.remove('okec-methods--hidden');
    if (!methods || !methods.length) {
      box.innerHTML = '<p class="okec-methods__empty">' + escapeHtml(TEXT.no_shipping) + '</p>';
      return;
    }
    var auto = box.getAttribute('data-auto-select') === '1';
    var html = '';
    var first = null;
    methods.forEach(function (m) {
      html += '<div class="okec-method__group"><h4 class="okec-method__title">' + escapeHtml(m.title) + '</h4>';
      if (m.error) {
        html += '<p class="okec-method__error">' + escapeHtml(m.error) + '</p>';
      } else {
        m.options.forEach(function (opt) {
          if (!first) first = opt.code;
          var icon = opt.icon ? '<span class="okec-method__icon"><img src="' + escapeAttr(opt.icon) + '" alt=""></span>' : '';
          var desc = opt.description ? '<span class="okec-method__desc">' + escapeHtml(opt.description) + '</span>' : '';
          html += '<label class="okec-method__option">' +
                  '<input type="radio" name="shipping_method" value="' + escapeAttr(opt.code) + '">' +
                  icon +
                  '<span class="okec-method__body"><span class="okec-method__name">' + escapeHtml(opt.title) + '</span>' + desc + '</span>' +
                  '<span class="okec-method__cost">' + escapeHtml(opt.text) + '</span>' +
                  '</label>';
        });
      }
      html += '</div>';
    });
    box.innerHTML = html;
    // Відновлюємо вибір (пріоритет: попередній, або auto-перший)
    var toSelect = prevSelected || (auto ? first : '');
    if (toSelect) {
      var radio = box.querySelector('input[type="radio"][value="' + cssEscape(toSelect) + '"]');
      if (radio) radio.checked = true;
    }
  }

  function renderPayment(box, methods) {
    var prevSelected = (box.querySelector('input[type="radio"]:checked') || {}).value || '';
    box.innerHTML = '';
    if (!methods || !methods.length) {
      box.innerHTML = '<p class="okec-methods__empty">' + escapeHtml(TEXT.no_payment) + '</p>';
      return;
    }
    var auto = box.getAttribute('data-auto-select') === '1';
    var html = '';
    methods.forEach(function (m) {
      var icon = m.icon ? '<span class="okec-method__icon"><img src="' + escapeAttr(m.icon) + '" alt=""></span>' : '';
      var desc = m.description ? '<span class="okec-method__desc">' + escapeHtml(m.description) + '</span>' : '';
      html += '<label class="okec-method__option">' +
              '<input type="radio" name="payment_method" value="' + escapeAttr(m.code) + '">' +
              icon +
              '<span class="okec-method__body"><span class="okec-method__name">' + escapeHtml(m.title) + '</span>' + desc + '</span>' +
              '</label>';
      if (m.terms) html += '<p class="okec-method__terms">' + escapeHtml(m.terms) + '</p>';
    });
    box.innerHTML = html;
    var toSelect = prevSelected || (auto && methods.length ? methods[0].code : '');
    if (toSelect) {
      var radio = box.querySelector('input[type="radio"][value="' + cssEscape(toSelect) + '"]');
      if (radio) radio.checked = true;
    }
  }

  function updateSubmitAmount(totals) {
    var slot = document.querySelector('[data-okec-submit-amount]');
    if (!slot) return;
    var totalRow = (totals || []).filter(function (t) { return t.code === 'total'; })[0];
    if (totalRow && totalRow.text) {
      slot.textContent = ' · ' + totalRow.text;
    } else {
      slot.textContent = '';
    }
  }

  function renderSummary(totals) {
    var box = document.querySelector('[data-okec-summary]'); if (!box) return;
    var table = box.querySelector('[data-okec-summary-table] tbody'); if (!table) return;
    var showSub = box.getAttribute('data-show-subtotal') === '1';
    var showTax = box.getAttribute('data-show-taxes')    === '1';
    var html = '';
    totals.forEach(function (t) {
      if (t.code === 'sub_total' && !showSub) return;
      if (t.code === 'tax'       && !showTax) return;
      html += '<tr class="okec-block-summary__row okec-block-summary__row--' + escapeAttr(t.code) + '">' +
              '<td class="okec-block-summary__label">' + escapeHtml(t.title) + '</td>' +
              '<td class="okec-block-summary__value">' + escapeHtml(t.text)  + '</td>' +
              '</tr>';
    });
    table.innerHTML = html;
  }

  // ─── Coupon / voucher / reward apply ────────────────────────────────────
  function handleApplyDiscount(action) {
    var map = {
      'apply-coupon':  { url: URLS.apply_coupon,  field: 'coupon'  },
      'apply-voucher': { url: URLS.apply_voucher, field: 'voucher' },
      'apply-reward':  { url: URLS.apply_reward,  field: 'reward'  },
    };
    var def = map[action]; if (!def || !def.url) return;
    var input = document.querySelector('[name="okec[' + def.field + ']"]'); if (!input) return;
    var val = (input.value || '').trim(); if (!val) return;

    var body = {}; body[def.field] = val;
    postJson(def.url, body)
      .then(function (data) {
        if (data && data.error) {
          notify(typeof data.error === 'string' ? data.error : (data.error.warning || ''), 'error');
        } else if (data && data.success) {
          notify(typeof data.success === 'string' ? data.success : 'Applied', 'success');
        }
        scheduleLoadMethods();
      })
      .catch(function () { /* silent */ });
  }

  function escapeHtml(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c];
    });
  }
  function escapeAttr(s) { return escapeHtml(s); }
  function cssEscape(s) { return String(s).replace(/(["\\])/g, '\\$1'); }

  // ─── Country → Zone cascade ─────────────────────────────────────────────
  function bindCountryCascade(scope) {
    var countrySelect = (scope || document).querySelector('[name="okec[country_id]"]');
    var zoneSelect    = (scope || document).querySelector('[name="okec[zone_id]"]');
    if (!countrySelect) return;

    var applyPhoneMask = function () {
      var opt = countrySelect.options[countrySelect.selectedIndex];
      if (!opt) return;
      var mask = opt.getAttribute('data-phone-mask') || '';
      if (!mask) return;
      // Усі telephone-input-и (native або custom з типом tel)
      document.querySelectorAll('input[name="okec[telephone]"], input[type="tel"]').forEach(function (el) {
        // Скидаємо існуючий IMask якщо був і ставимо новий
        if (el._imask) { try { el._imask.destroy(); } catch (e) {} el._imask = null; }
        el.setAttribute('data-okec-mask', mask);
      });
      bindMasks(document); // re-bind для оновлених data-okec-mask
    };

    if (zoneSelect) {
      countrySelect.addEventListener('change', function () {
        loadZones(parseInt(countrySelect.value, 10) || 0, zoneSelect);
        applyPhoneMask();
      });
      if (countrySelect.value && zoneSelect.options.length <= 1) {
        loadZones(parseInt(countrySelect.value, 10) || 0, zoneSelect);
      }
    } else {
      countrySelect.addEventListener('change', applyPhoneMask);
    }
    applyPhoneMask(); // initial — якщо country pre-selected
  }
  function loadZones(countryId, zoneSelect) {
    if (!countryId) return;
    getJson(URLS.load_zones + '&country_id=' + countryId)
      .then(function (data) {
        if (!data || !data.success) return;
        // Якщо нічого не вибрано — fallback на prefill з конфігу (для logged-in користувачів)
        var current = zoneSelect.value || (cfg.prefill && cfg.prefill.zone_id ? String(cfg.prefill.zone_id) : '');
        var ph = zoneSelect.querySelector('option[value=""]');
        zoneSelect.innerHTML = '';
        if (ph) zoneSelect.appendChild(ph);
        else {
          var o = document.createElement('option'); o.value = ''; o.textContent = '';
          zoneSelect.appendChild(o);
        }
        data.zones.forEach(function (z) {
          var o = document.createElement('option');
          o.value = String(z.zone_id);
          o.textContent = z.name;
          if (String(z.zone_id) === current) o.selected = true;
          zoneSelect.appendChild(o);
        });
      });
  }

  // ─── Conditional visibility — fields AND blocks ─────────────────────────
  // Селектор `[data-okec-cond]` ловить і `.okec-field`, і `.okec-block`.
  // data-okec-cond — JSON: { match: 'all'|'any', rules: [{source_code,operator,value}] }
  // Operators: ==, !=, not_empty, empty, in (CSV у value).
  function bindConditionalFields(scope) {
    var conds = (scope || document).querySelectorAll('[data-okec-cond]');
    if (!conds.length) return;

    var evalRule = function (sourceVal, op, expected) {
      sourceVal = String(sourceVal == null ? '' : sourceVal);
      expected  = String(expected == null ? '' : expected);
      switch (op) {
        case '==':         return sourceVal === expected;
        case '!=':         return sourceVal !== expected;
        case 'not_empty':  return sourceVal !== '';
        case 'empty':      return sourceVal === '';
        case 'in':         return expected.split(',').map(function (s) { return s.trim(); }).indexOf(sourceVal) !== -1;
      }
      return true;
    };

    var getFieldValue = function (code) {
      var el = document.querySelector('[name="okec[' + code + ']"]:checked')
            || document.querySelector('[name="okec[' + code + ']"]');
      if (!el) return '';
      if (el.type === 'checkbox') return el.checked ? (el.value || '1') : '';
      return el.value || '';
    };

    // Нормалізує умову до { match, rules[] } (legacy single → масив з 1)
    var parseCond = function (raw) {
      if (!raw) return null;
      var c;
      try { c = JSON.parse(raw); } catch (e) { return null; }
      if (!c) return null;
      if (Array.isArray(c.rules)) {
        return { match: c.match === 'any' ? 'any' : 'all', rules: c.rules };
      }
      if (typeof c.source_code !== 'undefined') {
        return { match: 'all', rules: [{ source_code: c.source_code, operator: c.operator || '==', value: c.value || '' }] };
      }
      return null;
    };

    var evalCond = function (cond) {
      if (!cond || !cond.rules.length) return true;
      var results = cond.rules.map(function (r) {
        if (!r.source_code) return true;
        return evalRule(getFieldValue(r.source_code), r.operator || '==', r.value || '');
      });
      return cond.match === 'any'
        ? results.some(function (x) { return x; })
        : results.every(function (x) { return x; });
    };

    var parsed = [];
    conds.forEach(function (box) { parsed.push({ box: box, cond: parseCond(box.getAttribute('data-okec-cond')) }); });

    var apply = function () {
      parsed.forEach(function (entry) {
        var visible = evalCond(entry.cond);
        entry.box.style.display = visible ? '' : 'none';
        // Disable inputs у прихованих полях — щоб не йшли в getState/serialize
        entry.box.querySelectorAll('input,select,textarea').forEach(function (el) {
          el.disabled = !visible;
        });
      });
    };

    apply();
    // Реагуємо на change і input event делегацією на root
    (scope || document).addEventListener('change', apply);
    (scope || document).addEventListener('input',  apply);
  }

  // ─── Same-as-shipping toggle для payment_address блоку ─────────────────
  function bindSameAsShipping(scope) {
    var cb = (scope || document).querySelector('[data-okec-same-as-shipping]');
    if (!cb) return;
    var fieldsWrap = (scope || document).querySelector('[data-okec-payment-fields]');
    if (!fieldsWrap) return;

    var apply = function () {
      var same = cb.checked;
      fieldsWrap.style.display = same ? 'none' : '';
      // Disabled inputs не потрапляють у getState — server отримає лише shipping
      fieldsWrap.querySelectorAll('input,select,textarea').forEach(function (el) {
        el.disabled = same;
      });
    };
    apply();
    cb.addEventListener('change', apply);
  }

  // ─── Address-book picker (logged-in users з кількома адресами) ─────────
  function bindAddressPicker(scope) {
    var book = (cfg.address_book || []);
    if (!book.length) return;
    var byId = {};
    book.forEach(function (a) { byId[String(a.address_id)] = a; });
    var pickers = (scope || document).querySelectorAll('[data-okec-address-picker]');

    var apply = function (a) {
      ['firstname','lastname','company','address_1','address_2','city','postcode'].forEach(function (k) {
        var inp = document.querySelector('[name="okec[' + k + ']"]');
        if (inp) { inp.value = a[k] || ''; inp.dispatchEvent(new Event('input', { bubbles: true })); }
      });
      var country = document.querySelector('[name="okec[country_id]"]');
      var zone    = document.querySelector('[name="okec[zone_id]"]');
      if (country && a.country_id) {
        country.value = String(a.country_id);
        // Збережемо потрібний zone — loadZones потім вибере його з prefill
        if (zone && a.zone_id) cfg.prefill = Object.assign(cfg.prefill || {}, { zone_id: a.zone_id });
        country.dispatchEvent(new Event('change', { bubbles: true }));
      }
      scheduleLoadMethods();
    };

    pickers.forEach(function (sel) {
      sel.addEventListener('change', function () {
        var a = byId[sel.value];
        if (a) apply(a);
      });
      // Sync інших picker-ів якщо їх кілька (shipping + payment)
      pickers.forEach(function (other) {
        if (other !== sel && other.value !== sel.value) other.value = sel.value;
      });
    });
  }

  // ─── Register toggle: показуємо password/confirm лише коли register checked ──
  function bindRegisterToggle(scope) {
    var registerInp = (scope || document).querySelector('input[name="okec[register]"]');
    if (!registerInp) return;
    var passField    = (scope || document).querySelector('[name="okec[password]"]');
    var confirmField = (scope || document).querySelector('[name="okec[confirm]"]');
    var passWrap    = passField    ? passField.closest('.okec-field')    : null;
    var confirmWrap = confirmField ? confirmField.closest('.okec-field') : null;

    var apply = function () {
      var on = !!registerInp.checked;
      [passWrap, confirmWrap].forEach(function (wrap) {
        if (!wrap) return;
        wrap.style.display = on ? '' : 'none';
        var input = wrap.querySelector('input,select,textarea');
        if (input) input.disabled = !on;
      });
    };
    apply();
    registerInp.addEventListener('change', apply);
  }

  // ─── File upload (data-okec-upload) ────────────────────────────────────
  function bindFileUpload(scope) {
    if (!URLS.upload_file) return;
    (scope || document).querySelectorAll('input[type="file"][data-okec-upload]').forEach(function (inp) {
      inp.addEventListener('change', function () {
        var wrap   = inp.closest('.okec-field__file');
        var status = wrap ? wrap.querySelector('[data-okec-upload-status]') : null;
        var hidden = wrap ? wrap.querySelector('input[type="hidden"]')      : null;
        if (!inp.files || !inp.files[0] || !hidden) return;

        var fd = new FormData();
        fd.append('file', inp.files[0]);
        if (status) status.textContent = '...';
        fetch(URLS.upload_file, { method: 'POST', credentials: 'same-origin', body: fd })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (data && data.code) {
              hidden.value = data.code;
              if (status) status.textContent = data.filename || data.success || 'OK';
              if (status) status.classList.add('okec-field__file-status--ok');
            } else {
              if (status) {
                status.textContent = (data && data.error) || 'Upload failed';
                status.classList.add('okec-field__file-status--err');
              }
              hidden.value = '';
              inp.value = '';
            }
          })
          .catch(function () {
            if (status) { status.textContent = 'Upload failed'; status.classList.add('okec-field__file-status--err'); }
            hidden.value = ''; inp.value = '';
          });
      });
    });
  }

  // ─── Date — disable weekends per field config ──────────────────────────
  function bindDateWeekendValidation(scope) {
    (scope || document).querySelectorAll('input[type="date"][data-okec-date-weekends]').forEach(function (inp) {
      var weekends = (inp.getAttribute('data-okec-date-weekends') || '').split(',').map(function (n) { return parseInt(n, 10); });
      inp.addEventListener('change', function () {
        if (!inp.value) return;
        var d = new Date(inp.value + 'T00:00:00');
        if (weekends.indexOf(d.getDay()) !== -1) {
          inp.setCustomValidity('Selected day not allowed');
          inp.reportValidity();
          inp.value = '';
        } else {
          inp.setCustomValidity('');
        }
      });
    });
  }

  // ─── Login modal ────────────────────────────────────────────────────────
  function openLoginModal() {
    var modal = document.querySelector('[data-okec-login-modal]');
    if (!modal) return;
    modal.hidden = false;
    document.body.classList.add('okec-no-scroll');
    var emailInput = modal.querySelector('input[name="email"]');
    // Префілимо з checkout-форми, якщо вже введено
    var checkoutEmail = document.querySelector('[name="okec[email]"]');
    if (emailInput && checkoutEmail && checkoutEmail.value) emailInput.value = checkoutEmail.value;
    setTimeout(function () { (emailInput && !emailInput.value ? emailInput : modal.querySelector('input[name="password"]')).focus(); }, 50);
  }
  function closeLoginModal() {
    var modal = document.querySelector('[data-okec-login-modal]');
    if (!modal) return;
    modal.hidden = true;
    document.body.classList.remove('okec-no-scroll');
    var err = modal.querySelector('[data-okec-login-error]');
    if (err) { err.hidden = true; err.textContent = ''; }
  }
  function bindLoginModal() {
    var modal = document.querySelector('[data-okec-login-modal]');
    if (!modal) return;
    modal.querySelectorAll('[data-okec-login-close]').forEach(function (el) {
      el.addEventListener('click', closeLoginModal);
    });
    // Клік на backdrop (за межами .ok-modal) — закриває
    modal.addEventListener('click', function (e) {
      if (e.target === modal) closeLoginModal();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !modal.hidden) closeLoginModal();
    });

    var form   = modal.querySelector('[data-okec-login-form]');
    var submit = modal.querySelector('[data-okec-login-submit]');
    if (!form || !submit) return;

    var doLogin = function () {
      var err   = modal.querySelector('[data-okec-login-error]');
      var email = form.querySelector('input[name="email"]').value.trim();
      var pass  = form.querySelector('input[name="password"]').value;
      if (!email || !pass) return;
      submit.disabled = true;
      if (err) { err.hidden = true; err.textContent = ''; }
      postJson(URLS.login_customer, { email: email, password: pass })
        .then(function (data) {
          submit.disabled = false;
          if (data && data.success) {
            window.location.href = data.redirect || window.location.href;
          } else if (err) {
            err.textContent = (data && data.error) || 'Login failed';
            err.hidden = false;
          }
        })
        .catch(function () {
          submit.disabled = false;
          if (err) { err.textContent = TEXT.cart_failed || 'Network error'; err.hidden = false; }
        });
    };

    submit.addEventListener('click', doLogin);
    form.addEventListener('submit', function (e) { e.preventDefault(); doLogin(); });
  }

  // ─── Existing-email hint ────────────────────────────────────────────────
  function bindEmailExistenceCheck(scope) {
    var emailInput = (scope || document).querySelector('[name="okec[email]"]');
    var hint       = (scope || document).querySelector('[data-okec-account-exists]');
    if (!emailInput || !hint || !URLS.check_email) return;
    var checkTimer = null;
    var run = function () {
      var v = (emailInput.value || '').trim();
      if (!v || v.indexOf('@') === -1) { hint.style.display = 'none'; return; }
      postJson(URLS.check_email, { email: v })
        .then(function (data) {
          hint.style.display = (data && data.exists) ? '' : 'none';
        })
        .catch(function () { /* silent */ });
    };
    emailInput.addEventListener('blur', run);
    emailInput.addEventListener('input', function () {
      clearTimeout(checkTimer); checkTimer = setTimeout(run, 600);
    });
  }

  // ─── Submit / confirm ───────────────────────────────────────────────────
  function triggerSubmit() {
    var btn = document.querySelector('[data-okec-action="confirm"]');

    // Якщо є debounced loadMethods в очікуванні — flush негайно і чекаємо завершення
    if (loadMethodsTimer) {
      clearTimeout(loadMethodsTimer);
      loadMethodsTimer = null;
      if (btn) { btn.disabled = true; btn.classList.add('okec-btn--loading'); }
      loadMethods();
    }
    if (loadMethodsInflight && typeof loadMethodsInflight.then === 'function') {
      if (btn) { btn.disabled = true; btn.classList.add('okec-btn--loading'); }
      loadMethodsInflight.then(function () {
        if (btn) { btn.disabled = false; btn.classList.remove('okec-btn--loading'); }
        triggerSubmit();   // recursive — стан вже актуальний, цього разу пропустить wait
      });
      return;
    }

    // Якщо submit заблокований через відсутність shipping/payment методів — нотифікація
    if (latestMethodsState.has_shipping && latestMethodsState.shipping_count === 0) {
      notify(TEXT.no_shipping, 'warning'); return;
    }
    if (latestMethodsState.payment_count === 0) {
      notify(TEXT.no_payment, 'warning'); return;
    }

    // Client-side validation: required-input/select/textarea без значення
    var clientErrors = collectClientErrors();
    if (Object.keys(clientErrors).length) {
      showErrors(clientErrors, null);
      return;
    }

    if (btn) {
      btn.disabled = true;
      btn.classList.add('okec-btn--loading');
    }

    var state = api.getState();
    var shippingMethod = (document.querySelector('input[name="shipping_method"]:checked') || {}).value || '';
    var paymentMethod  = (document.querySelector('input[name="payment_method"]:checked') || {}).value  || '';

    api.trigger('okec:beforeConfirm', { state: state });

    var resetBtn = function () {
      if (btn) { btn.disabled = false; btn.classList.remove('okec-btn--loading'); }
    };

    postJson(URLS.confirm, {
      okec:            state,
      shipping_method: shippingMethod,
      payment_method:  paymentMethod,
    })
      .then(function (data) {
        if (data && data.success) {
          // Redirect-style оплата (mono тощо): форма = просто кнопка-лінк на
          // gateway, без полів вводу → одразу ведемо на оплату, без зайвого кроку.
          if (data.payment_form && autoProceedPayment(data.payment_form)) {
            return; // redirecting to gateway
          }
          // Inline payment form: рендеримо HTML у payment_form блок
          // (модулі з полями вводу / інструкціями — лишаємо крок підтвердження).
          if (data.payment_form && renderPaymentFormInline(data.payment_form)) {
            return;
          }
          // Fallback — модуль не сумісний з inline (e.g. якщо повернув порожній output)
          window.location.href = data.redirect || window.location.href;
          return;
        }
        resetBtn();
        showErrors(data && data.errors ? data.errors : null,
                   (data && data.error) || TEXT.submit_failed);
      })
      .catch(function () {
        resetBtn();
        notify(TEXT.submit_failed, 'error');
      });
  }

  /**
   * Redirect-style payment: якщо повернута payment-form — це лише кнопка-лінк на
   * зовнішній gateway (немає полів вводу), ведемо одразу на оплату без кроку
   * «Підтвердити замовлення». Повертає true якщо почали редірект.
   */
  function autoProceedPayment(html) {
    if (!html) return false;
    var tmp = document.createElement('div');
    tmp.innerHTML = html;
    // Якщо форма потребує вводу (картка, інструкції з полями) — не авто-проходимо.
    if (tmp.querySelector('input:not([type=hidden]):not([type=button]):not([type=submit]), select, textarea')) {
      return false;
    }
    // Шукаємо посилання на зовнішній gateway (http/https), напр. mono checkout_url.
    var anchors = tmp.querySelectorAll('a[href]');
    for (var i = 0; i < anchors.length; i++) {
      var href = anchors[i].getAttribute('href');
      if (href && /^https?:\/\//i.test(href)) {
        window.location.href = href;
        return true;
      }
    }
    return false;
  }

  /**
   * Рендерить HTML платіжної форми у payment_form блок (або в payment-блок як fallback).
   * Приховує всі інші блоки checkout-у і блокує редагування — користувач має
   * завершити оплату або натиснути "Edit checkout".
   * Повертає true якщо знайшов слот для рендеру.
   */
  function renderPaymentFormInline(html) {
    if (!html) return false;
    var slot = document.querySelector('[data-okec-payment-form]')
            || document.querySelector('.okec-block--payment_form .okec-block__inner')
            || document.querySelector('.okec-block--payment .okec-block__inner');
    if (!slot) return false;

    // Приховуємо всі блоки крім того, де рендеримо payment-form (та summary)
    var keepers = [slot.closest('.okec-block'), document.querySelector('.okec-block--summary'), document.querySelector('.okec-block--cart')].filter(Boolean);
    document.querySelectorAll('.okec-checkout .okec-block').forEach(function (b) {
      if (keepers.indexOf(b) === -1) b.classList.add('okec-block--hidden-after-confirm');
    });
    // Розкладку → 1 колонка для зосередження
    document.querySelectorAll('.okec-checkout__row').forEach(function (r) {
      r.classList.add('okec-checkout__row--collapsed');
    });

    slot.innerHTML = html;
    // Виконати <script> блоки з відповіді
    slot.querySelectorAll('script').forEach(function (origScript) {
      var s = document.createElement('script');
      if (origScript.src) s.src = origScript.src;
      else s.textContent = origScript.textContent;
      origScript.parentNode.replaceChild(s, origScript);
    });

    // Кнопка "Edit checkout" — щоб користувач міг повернутися
    var back = document.createElement('p');
    back.className = 'okec-payment-back';
    back.innerHTML = '<a href="#" data-okec-action="edit-checkout">' +
                     '<i class="fa fa-chevron-left"></i> ' +
                     escapeHtml(TEXT.back_to_checkout || 'Back to checkout') +
                     '</a>';
    slot.appendChild(back);

    api.trigger('okec:paymentFormRendered', {});
    slot.scrollIntoView({ behavior: 'smooth', block: 'start' });
    return true;
  }

  /** Збирає client-side помилки: required fields + checkbox-required (agreement). */
  function collectClientErrors() {
    var errors = {};
    document.querySelectorAll('.okec-field input[required], .okec-field select[required], .okec-field textarea[required]').forEach(function (el) {
      if (el.disabled) return;
      var box = el.closest('.okec-field'); if (!box) return;
      var code = box.getAttribute('data-field-code'); if (!code) return;
      var name = (el.name.match(/^okec\[(.+)\]$/) || [])[1] || code;
      var ok;
      if (el.type === 'checkbox' || el.type === 'radio') {
        // Для radio — група має мати хоч один checked
        if (el.type === 'radio') {
          ok = !!document.querySelector('input[name="' + el.name + '"]:checked');
        } else {
          ok = el.checked;
        }
      } else {
        ok = (el.value || '').trim() !== '';
      }
      if (!ok && !errors[name]) {
        errors[name] = (TEXT.field_required || 'Required');
      }
    });
    // Agreement checkbox required
    var agreeBox = document.querySelector('.okec-block-agreement input[type="checkbox"]');
    if (agreeBox && agreeBox.required && !agreeBox.checked) {
      errors['agreement'] = TEXT.field_required || 'Required';
    }
    return errors;
  }

  function clearErrors() {
    document.querySelectorAll('.okec-field--error, .okec-block-agreement--error').forEach(function (el) {
      el.classList.remove('okec-field--error', 'okec-block-agreement--error');
      var msg = el.querySelector('.okec-field__error'); if (msg) msg.remove();
    });
  }

  function showErrors(errorsByCode, fallbackMsg) {
    clearErrors();
    if (errorsByCode) {
      Object.keys(errorsByCode).forEach(function (code) {
        var input = document.querySelector('[name="okec[' + code + ']"]');
        if (!input) return;
        // Поле всередині .okec-field — звичне підсвічування.
        // Згода (agreement) живе в .okec-block-agreement (не .okec-field).
        var box = input.closest('.okec-field') || input.closest('.okec-block-agreement');
        if (!box) return;
        box.classList.add(box.classList.contains('okec-field') ? 'okec-field--error' : 'okec-block-agreement--error');
        var msg = document.createElement('span');
        msg.className = 'okec-field__error';
        msg.textContent = errorsByCode[code];
        box.appendChild(msg);
      });
      var firstBox = document.querySelector('.okec-field--error, .okec-block-agreement--error');
      if (firstBox) {
        firstBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
        var firstInput = firstBox.querySelector('input, select, textarea');
        if (firstInput) setTimeout(function () { firstInput.focus(); }, 350);
      }
    } else if (fallbackMsg) {
      notify(fallbackMsg, 'error');
    }
  }

  // ─── Field change handler ──────────────────────────────────────────────
  // Поля, що ВЖЕ ВПЛИВАЮТЬ на доставку/оплату — завжди тригерять reload
  // (незалежно від адмін-флагу), бо без них shipping_methods не порахується.
  var ADDRESS_RELOAD_CODES = ['country', 'zone', 'postcode', 'city', 'address_1'];

  function handleFieldChange(el) {
    var fieldDiv = el.closest('.okec-field'); if (!fieldDiv) return;
    // Прибираємо error-стан як тільки користувач починає виправляти
    if (fieldDiv.classList.contains('okec-field--error')) {
      fieldDiv.classList.remove('okec-field--error');
      var msg = fieldDiv.querySelector('.okec-field__error');
      if (msg) msg.remove();
    }
    var code = fieldDiv.getAttribute('data-field-code');
    var payload = {
      code:     code,
      field_id: parseInt(fieldDiv.getAttribute('data-field-id'), 10) || 0,
      value:    (el.type === 'checkbox' || el.type === 'radio') ? (el.checked ? el.value : '') : el.value,
      reload:   fieldDiv.hasAttribute('data-okec-reload') || ADDRESS_RELOAD_CODES.indexOf(code) !== -1,
    };
    api.trigger('okec:fieldChange', payload);
    if (payload.reload) {
      api.trigger('okec:reloadRequested', payload);
      scheduleLoadMethods();
    }
  }

  // ─── Boot ───────────────────────────────────────────────────────────────
  ready(function () {
    var root = document.querySelector('.okec-checkout');
    if (!root) return;

    bindMasks(root);
    bindCountryCascade(root);
    bindEmailExistenceCheck(root);
    bindLoginModal();
    bindDateWeekendValidation(root);
    bindFileUpload(root);
    bindRegisterToggle(root);
    bindAddressPicker(root);
    bindSameAsShipping(root);
    bindConditionalFields(root);

    root.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-okec-action]'); if (!btn) return;
      var action = btn.getAttribute('data-okec-action');
      if (action === 'qty-increment' || action === 'qty-decrement' || action === 'cart-remove') {
        e.preventDefault(); handleCartAction(btn); return;
      }
      if (action === 'apply-coupon' || action === 'apply-voucher' || action === 'apply-reward') {
        e.preventDefault(); handleApplyDiscount(action); return;
      }
      if (action === 'login') {
        e.preventDefault();
        openLoginModal();
        return;
      }
      if (action === 'confirm') { e.preventDefault(); triggerSubmit(); return; }
      if (action === 'edit-checkout') {
        e.preventDefault();
        // Reload без redirect-параметрів — користувач повертається до форми.
        // session.data['order_id'] лишається — payment-link все ще валідний;
        // OC при наступному payment-callback закриє order правильно.
        window.location.reload();
        return;
      }
    });

    root.addEventListener('change', function (e) {
      // Зміна обраного shipping/payment — тригеримо reload, щоб totals оновились
      if (e.target.matches('input[name="shipping_method"]')) {
        api.trigger('okec:shippingSelect', { code: e.target.value });
        scheduleLoadMethods();
        return;
      }
      if (e.target.matches('input[name="payment_method"]')) {
        api.trigger('okec:paymentSelect', { code: e.target.value });
        scheduleLoadMethods();
        return;
      }
      // Відмітив згоду — знімаємо підсвічування помилки.
      if (e.target.matches('.okec-block-agreement input[type="checkbox"]') && e.target.checked) {
        var ab = e.target.closest('.okec-block-agreement--error');
        if (ab) { ab.classList.remove('okec-block-agreement--error'); var m = ab.querySelector('.okec-field__error'); if (m) m.remove(); }
      }
      if (e.target.closest('.okec-field')) handleFieldChange(e.target);
    });

    // §11.2 — focus/blur на полях для зовнішніх інтеграцій
    root.addEventListener('focusin', function (e) {
      var f = e.target.closest && e.target.closest('.okec-field');
      if (!f) return;
      var input = e.target;
      var code  = (input.name || '').replace(/^okec\[(.+)\]$/, '$1');
      if (code) api.trigger('okec:fieldFocus', { code: code });
    });
    root.addEventListener('focusout', function (e) {
      var f = e.target.closest && e.target.closest('.okec-field');
      if (!f) return;
      var input = e.target;
      var code  = (input.name || '').replace(/^okec\[(.+)\]$/, '$1');
      if (code) {
        api.trigger('okec:fieldBlur', { code: code, value: input.value });
        // §15.1: на blur значимого поля — зберігаємо abandoned snapshot
        if (['email', 'telephone', 'firstname', 'lastname'].indexOf(code) !== -1) {
          scheduleSaveAbandoned();
        }
      }
    });

    var _saveAbandonedTimer = null, _saveAbandonedInflight = false;
    function scheduleSaveAbandoned() {
      clearTimeout(_saveAbandonedTimer);
      _saveAbandonedTimer = setTimeout(saveAbandoned, 600);
    }
    function saveAbandoned() {
      if (_saveAbandonedInflight) return;
      if (!URLS.save_abandoned) return;
      var st = api.getState();
      if (!st.email && !st.telephone) return;
      _saveAbandonedInflight = true;
      var body = serializeForm({ okec: st });
      fetch(URLS.save_abandoned, {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body,
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          _saveAbandonedInflight = false;
          if (data && data.success && data.abandoned_id) {
            api.trigger('okec:abandonedSaved', { abandoned_id: data.abandoned_id });
          }
        })
        .catch(function () { _saveAbandonedInflight = false; });
    }

    root.addEventListener('input', function (e) {
      if (e.target.closest('.okec-field') &&
          (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA')) {
        handleFieldChange(e.target);
      }
    });

    api.trigger('okec:ready', {
      groupId: parseInt(root.getAttribute('data-group-id'), 10) || 0,
      mode:    root.getAttribute('data-mode') || 'single_step',
      state:   api.getState(),
    });

    // Перший виклик loadMethods — щоб одразу показати методи (якщо в session є адреса)
    scheduleLoadMethods();
  });
}());
