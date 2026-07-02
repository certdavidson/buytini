/**
 * EasyCheckout — Standalone section components (Alpine factories)
 * © 2026 oc-kit.com | https://oc-kit.com
 *
 * Кожен factory створює незалежну Alpine-компоненту для конкретної admin-секції,
 * що читає/пише через `window.OkEasycheckoutContext.urls`.
 * Файл завантажується ПЕРЕД alpine.min.js — гарантує що всі `okecXxx` визначені
 * до моменту коли Alpine evaluate x-data.
 */
(function () {
  'use strict';

  function ctxUrls() { return ((window.OkEasycheckoutContext || {}).urls || {}); }
  function tFn(k)    { return (window.OkEasycheckoutI18n && window.OkEasycheckoutI18n[k]) || k; }
  function tickIcons() {
    if (window.Alpine && window.lucide) {
      window.Alpine.nextTick(function () { window.lucide.createIcons(); });
    }
  }

  // ─── Layout presets gallery (Presets sidebar tab) ──────────────────────
  window.okecPresets = function () {
    return {
      loading: false,
      presets: [],
      t: tFn,
      load: function () {
        var self = this;
        var url = ctxUrls().layout_presets_list;
        if (!url) return;
        self.loading = true;
        fetch(url, { credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            self.loading = false;
            self.presets = (data && data.presets) || [];
            tickIcons();
          })
          .catch(function () { self.loading = false; });
      },
      apply: function (p) {
        if (!confirm(tFn('preset_apply_confirm'))) return;
        var ref = window.OkecAppRef || {};
        window.okPost(ctxUrls().layout_preset_apply, {
          code:     p.code,
          group_id: ref.activeGroupId || 0,
          store_id: (ref.pages && ref.pages.activeStoreId) || 0,
        }, function (data) {
          if (data && data.success) {
            window.okNotify(data.message || tFn('preset_applied'), 'success');
            if (ref.pages && typeof ref.pages.load === 'function') ref.pages.load();
          } else {
            window.okNotify((data && data.message) || tFn('js_error'), 'error');
          }
        });
      },
    };
  };

  // ─── Health-check checks list ──────────────────────────────────────────
  window.okecHealth = function () {
    return {
      loading: false,
      checks:  [],
      t: tFn,
      load: function () {
        var self = this;
        var url = ctxUrls().health_check;
        if (!url) return;
        self.loading = true;
        fetch(url, { credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            self.loading = false;
            self.checks  = (data && data.checks) || [];
            tickIcons();
          })
          .catch(function () { self.loading = false; });
      },
    };
  };

  // ─── Address formats CRUD ──────────────────────────────────────────────
  window.okecAddressFormats = function () {
    var langMap = (window.OkEasycheckoutContext || {}).languages || [];
    return {
      loading: false,
      items: [],
      customerGroups: [],
      shippingMethods: [],
      pickerOpen: false,
      modalOpen: false,
      form: { format_id: 0, scope: 'shipping', scope_id: '', language_id: langMap[0] ? langMap[0].language_id : 1, template: '' },
      // Доступні плейсхолдери адреси (стандартні OC-токени)
      placeholders: ['firstname', 'lastname', 'company', 'address_1', 'address_2', 'city', 'postcode', 'zone', 'zone_code', 'country'],
      // Вставка токена в textarea шаблону на позицію курсора
      insertPlaceholder: function (token) {
        var tag = '{' + token + '}';
        var el = document.querySelector('#okec-addrfmt-template');
        if (!el) { this.form.template = (this.form.template || '') + tag; return; }
        var start = el.selectionStart || 0;
        var end   = el.selectionEnd   || 0;
        var val   = this.form.template || '';
        this.form.template = val.slice(0, start) + tag + val.slice(end);
        this.$nextTick(function () {
          el.focus();
          var pos = start + tag.length;
          el.setSelectionRange(pos, pos);
        });
      },
      t: tFn,
      languageById: function (id) {
        var l = langMap.find(function (x) { return parseInt(x.language_id, 10) === parseInt(id, 10); });
        return l ? l.name : id;
      },
      // ── scope_id multi-pick helpers ────────────────────────────
      parseScopeIds: function (csv) {
        if (csv == null || csv === '') return [];
        return String(csv).split(',').map(function (x) { return x.trim(); }).filter(Boolean);
      },
      serializeScopeIds: function (arr) { return arr.join(','); },
      hasScopeId: function (id) {
        return this.parseScopeIds(this.form.scope_id).indexOf(String(id)) !== -1;
      },
      toggleScopeId: function (id) {
        var ids = this.parseScopeIds(this.form.scope_id);
        var sid = String(id);
        var idx = ids.indexOf(sid);
        if (idx === -1) ids.push(sid);
        else ids.splice(idx, 1);
        this.form.scope_id = this.serializeScopeIds(ids);
      },
      removeScopeId: function (id) {
        var ids = this.parseScopeIds(this.form.scope_id).filter(function (x) { return x !== String(id); });
        this.form.scope_id = this.serializeScopeIds(ids);
      },
      scopeOptions: function (scope) {
        if (scope === 'customer_group') {
          return (this.customerGroups || []).map(function (g) {
            return { id: g.customer_group_id, label: g.name };
          });
        }
        return (this.shippingMethods || []).map(function (m) {
          return { id: m.code, label: m.title || m.code };
        });
      },
      scopeOptionLabel: function (scope, id) {
        var opt = this.scopeOptions(scope).find(function (o) { return String(o.id) === String(id); });
        return opt ? opt.label : id;
      },
      load: function () {
        var self = this;
        var url = ctxUrls().address_formats_list;
        if (!url) return;
        self.loading = true;
        fetch(url, { credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            self.loading = false;
            self.items = (data && data.items) || [];
            self.customerGroups = (data && data.customer_groups) || [];
            self.shippingMethods = (data && data.shipping_methods) || [];
            tickIcons();
          })
          .catch(function () { self.loading = false; });
      },
      openCreate: function () {
        this.form = { format_id: 0, scope: 'shipping', scope_id: '', language_id: langMap[0] ? langMap[0].language_id : 1, template: '' };
        this.modalOpen = true;
        tickIcons();
      },
      openEdit: function (f) {
        this.form = { format_id: f.format_id, scope: f.scope, scope_id: f.scope_id, language_id: f.language_id, template: f.template };
        this.modalOpen = true;
        tickIcons();
      },
      save: function () {
        var self = this;
        window.okPost(ctxUrls().address_format_save, self.form, function (data) {
          if (data && data.success) {
            window.okNotify(data.message || tFn('js_saved'), 'success');
            self.modalOpen = false;
            self.load();
          } else {
            window.okNotify((data && data.message) || tFn('js_error'), 'error');
          }
        });
      },
      del: function (f) {
        if (!confirm(tFn('js_confirm'))) return;
        var self = this;
        window.okPost(ctxUrls().address_format_delete, { format_id: f.format_id }, function (data) {
          if (data && data.success) self.load();
        });
      },
    };
  };

  // ─── Order restrictions CRUD ───────────────────────────────────────────
  window.okecRestrictions = function () {
    var emptyForm = function () {
      return {
        restriction_id: 0, group_id: 0, customer_group_ids: '',
        min_total: '', max_total: '', min_qty: '', max_qty: '',
        min_weight: '', max_weight: '', error_text: '', sort_order: 0,
      };
    };
    return {
      loading: false,
      items: [],
      customerGroups: [],
      groupPickerOpen: false,
      modalOpen: false,
      form: emptyForm(),
      t: tFn,
      // Convert CSV string to array of int ids (handles null/undefined safely for Alpine init)
      parseGroups: function (csv) {
        if (csv == null || csv === '' || csv === undefined) return [];
        return String(csv).split(',').map(function (x) { return parseInt(x.trim(), 10); }).filter(function (x) { return x > 0; });
      },
      // Serialize array to CSV
      serializeGroups: function (arr) { return arr.join(','); },
      groupName: function (id) {
        var g = this.customerGroups.find(function (x) { return x.customer_group_id === id; });
        return g ? g.name : ('#' + id);
      },
      hasGroup: function (id) {
        return this.parseGroups(this.form.customer_group_ids).indexOf(id) !== -1;
      },
      toggleGroup: function (id) {
        var ids = this.parseGroups(this.form.customer_group_ids);
        var idx = ids.indexOf(id);
        if (idx === -1) ids.push(id);
        else ids.splice(idx, 1);
        this.form.customer_group_ids = this.serializeGroups(ids);
      },
      removeGroup: function (id) {
        var ids = this.parseGroups(this.form.customer_group_ids).filter(function (x) { return x !== id; });
        this.form.customer_group_ids = this.serializeGroups(ids);
      },
      rangeLabel: function (a, b) {
        if (a == null && b == null) return '—';
        return (a == null ? '∅' : a) + ' / ' + (b == null ? '∅' : b);
      },
      load: function () {
        var self = this;
        var url = ctxUrls().restrictions_list;
        if (!url) return;
        self.loading = true;
        fetch(url, { credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            self.loading = false;
            self.items = (data && data.items) || [];
            self.customerGroups = (data && data.customer_groups) || [];
            tickIcons();
          })
          .catch(function () { self.loading = false; });
      },
      openCreate: function () {
        this.form = emptyForm();
        this.modalOpen = true;
        tickIcons();
      },
      openEdit: function (r) {
        var f = JSON.parse(JSON.stringify(r));
        ['min_total','max_total','min_qty','max_qty','min_weight','max_weight'].forEach(function (k) {
          if (f[k] === null) f[k] = '';
        });
        this.form = f;
        this.modalOpen = true;
        tickIcons();
      },
      save: function () {
        var self = this;
        window.okPost(ctxUrls().restriction_save, self.form, function (data) {
          if (data && data.success) {
            window.okNotify(data.message || tFn('js_saved'), 'success');
            self.modalOpen = false;
            self.load();
          } else {
            window.okNotify((data && data.message) || tFn('js_error'), 'error');
          }
        });
      },
      del: function (r) {
        if (!confirm(tFn('js_confirm'))) return;
        var self = this;
        window.okPost(ctxUrls().restriction_delete, { restriction_id: r.restriction_id }, function (data) {
          if (data && data.success) self.load();
        });
      },
    };
  };

  // ─── Payment / Shipping modules overrides ──────────────────────────────
  window.okecModules = function () {
    var ctx = window.OkEasycheckoutContext || {};
    return {
      loading: false,
      payment:  [],
      shipping: [],
      placeholder: ctx.image_placeholder || '',
      imageBase:   ctx.image_base || '',
      t: tFn,
      // Унікальні id для native filemanager-пікера (target = input, thumb = <a>)
      iconInputId: function (type, m) { return 'okec-modicon-' + type + '-' + String(m.code).replace(/[^a-z0-9_]/gi, '_'); },
      iconThumbId: function (type, m) { return 'okec-modthumb-' + type + '-' + String(m.code).replace(/[^a-z0-9_]/gi, '_'); },
      // URL для прев'ю: повний URL — як є; OC-шлях — через image-базу; порожньо — placeholder
      iconSrc: function (m) {
        var v = m.override_icon || '';
        if (!v) return this.placeholder;
        if (/^https?:\/\//i.test(v)) return v;
        return this.imageBase + v;
      },
      load: function () {
        var self = this;
        var url = ctxUrls().modules_list;
        if (!url) return;
        self.loading = true;
        fetch(url, { credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            self.loading = false;
            self.payment  = (data && data.payment)  || [];
            self.shipping = (data && data.shipping) || [];
            tickIcons();
          })
          .catch(function () { self.loading = false; });
      },
      save: function (type, m) {
        // Native filemanager пише значення прямо в hidden input (jQuery .val),
        // Alpine x-model це не ловить — зчитуємо з DOM перед відправкою.
        var inp = document.getElementById(this.iconInputId(type, m));
        if (inp) m.override_icon = inp.value;
        window.okPost(ctxUrls().module_override_save, {
          type:                 type,
          code:                 m.code,
          hide:                 m.hide ? 1 : 0,
          sort_order:           m.sort_order || 0,
          override_title:       m.override_title || '',
          override_description: m.override_description || '',
          override_icon:        m.override_icon || '',
        }, function (data) {
          if (data && data.success) window.okNotify(tFn('js_saved'), 'success');
          else                      window.okNotify((data && data.message) || tFn('js_error'), 'error');
        });
      },
    };
  };

  // ─── Custom shipping/payment methods (master-detail) ───────────────────
  window.okecCustomMethods = function () {
    var ctx = window.OkEasycheckoutContext || {};
    return {
      loading: false,
      shipping: { groups: [], methods: [] },
      payment:  { methods: [] },
      subtotals: [],
      conditionTypes: [],
      taxClasses: [],
      orderStatuses: [],
      currencies: [],
      languages: ctx.languages || [],
      placeholder: ctx.image_placeholder || '',
      imageBase:   ctx.image_base || '',
      current: null,        // обраний метод АБО subtotal (form state)
      currentKind: 'method',// 'method' | 'subtotal'
      activeLang: null,
      busy: false,
      t: tFn,

      load: function () {
        var self = this;
        var url = ctxUrls().cm_data;
        if (!url) return;
        self.loading = true;
        fetch(url, { credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            self.loading = false;
            if (!data || !data.success) return;
            self.shipping      = data.shipping || { groups: [], methods: [] };
            self.payment       = data.payment  || { methods: [] };
            self.subtotals     = (data.subtotals || []).map(function (s) { return self.normalizeSubtotal(s); });
            self.conditionTypes = data.condition_types || [];
            self.taxClasses    = data.tax_classes    || [];
            self.orderStatuses = data.order_statuses || [];
            self.currencies    = data.currencies     || [];
            if (!self.activeLang && self.languages.length) self.activeLang = self.languages[0].language_id;
            tickIcons();
          })
          .catch(function () { self.loading = false; });
      },

      // ── master: groups / methods ──────────────────────────────────────
      methodsInGroup: function (gid) {
        return (this.shipping.methods || []).filter(function (m) { return m.group_id === gid; });
      },
      ungroupedShipping: function () {
        var ids = (this.shipping.groups || []).map(function (g) { return g.group_id; });
        return (this.shipping.methods || []).filter(function (m) { return ids.indexOf(m.group_id) === -1; });
      },
      addGroup: function () {
        var self = this;
        window.okPost(ctxUrls().cm_group_add, { type: 'shipping' }, function (d) {
          if (d && d.success) self.load();
        });
      },
      delGroup: function (gid) {
        var self = this;
        if (!confirm(tFn('cm_confirm_delete_group'))) return;
        window.okPost(ctxUrls().cm_group_delete, { group_id: gid }, function (d) {
          if (d && d.success) self.load();
        });
      },
      addMethod: function (type, groupId) {
        var self = this;
        window.okPost(ctxUrls().cm_add, { type: type, group_id: groupId || 0 }, function (d) {
          if (d && d.success) { self.load(); self.edit(d.method_id); }
        });
      },
      toggle: function (m) {
        var self = this;
        window.okPost(ctxUrls().cm_toggle, { method_id: m.method_id, status: m.status ? 0 : 1 }, function (d) {
          if (d && d.success) m.status = m.status ? 0 : 1;
        });
      },
      cloneMethod: function (m) {
        var self = this;
        window.okPost(ctxUrls().cm_clone, { method_id: m.method_id }, function (d) {
          if (d && d.success) { self.load(); self.edit(d.method_id); }
        });
      },
      del: function (m) {
        var self = this;
        if (!confirm(tFn('cm_confirm_delete_method'))) return;
        window.okPost(ctxUrls().cm_delete, { method_id: m.method_id }, function (d) {
          if (d && d.success) { if (self.current && self.current.method_id === m.method_id) self.current = null; self.load(); }
        });
      },

      // ── detail: edit form ─────────────────────────────────────────────
      edit: function (methodId) {
        var self = this;
        fetch(ctxUrls().cm_get + '&method_id=' + encodeURIComponent(methodId), { credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (!data || !data.success) return;
            self.currentKind = 'method';
            self.current = self.normalizeForm(data.method);
            if (!self.activeLang && self.languages.length) self.activeLang = self.languages[0].language_id;
            tickIcons();
          });
      },
      editSubtotal: function (s) {
        this.currentKind = 'subtotal';
        this.current = this.normalizeSubtotal(s);
        // builder біндиться на current.conditions
        this.current.conditions = this.current.rules.conditions;
        if (!this.activeLang && this.languages.length) this.activeLang = this.languages[0].language_id;
        tickIcons();
      },
      isEditing: function (methodId) { return this.currentKind === 'method' && this.current && this.current.method_id === methodId; },
      isEditingSubtotal: function (id) { return this.currentKind === 'subtotal' && this.current && this.current.subtotal_id === id; },

      // Гарантуємо наявність descriptions для всіх мов + conditions shape
      normalizeForm: function (m) {
        var self = this;
        m.descriptions = m.descriptions || {};
        self.languages.forEach(function (l) {
          if (!m.descriptions[l.language_id]) {
            m.descriptions[l.language_id] = { name: '', description: '', zero_cost_text: '',
              payment_form_heading: '', payment_info_form: '', payment_info_mail: '' };
          }
        });
        if (!m.conditions || !Array.isArray(m.conditions.rules)) {
          m.conditions = { match: 'all', rules: [] };
        }
        // params приходить з PHP як [] (порожній JSON-масив) — у JS це Array, і
        // присвоєння .icon не переживає JSON.stringify. Приводимо до object.
        if (!m.params || typeof m.params !== 'object' || Array.isArray(m.params)) {
          m.params = {};
        }
        return m;
      },
      desc: function (langId) {
        if (!this.current) return {};
        if (!this.current.descriptions[langId]) {
          this.current.descriptions[langId] = { name: '', description: '', zero_cost_text: '',
            payment_form_heading: '', payment_info_form: '', payment_info_mail: '' };
        }
        return this.current.descriptions[langId];
      },

      // conditions builder (reuse {match, rules[]})
      addRule: function () {
        this.current.conditions.rules.push({ source_code: '', operator: '==', value: '' });
        tickIcons();   // нова rule-рядок має data-lucide — переініціалізуємо
      },
      removeRule: function (i) { this.current.conditions.rules.splice(i, 1); },

      // Типи умов, доступні для поточного контексту (groped), з урахуванням applies.
      // Для subtotal доступні ВСІ типи (і варіант оплати, і варіант доставки).
      condTypeGroups: function () {
        var kind = this.currentKind;
        var type = (this.current && this.current.type) ? this.current.type : 'shipping';
        var avail = (this.conditionTypes || []).filter(function (c) {
          if (kind === 'subtotal') return true;
          return c.applies === 'both' || c.applies === type;
        });
        var groups = [];
        avail.forEach(function (c) {
          var g = groups.find(function (x) { return x.key === c.group; });
          if (!g) { g = { key: c.group, label: c.group_label, items: [] }; groups.push(g); }
          g.items.push(c);
        });
        return groups;
      },

      // icon picker (native filemanager)
      iconInputId: function () { return 'okec-cm-icon-input'; },
      iconThumbId: function () { return 'okec-cm-icon-thumb'; },
      iconSrc: function () {
        var v = (this.current && this.current.params && this.current.params.icon) || '';
        if (!v) return this.placeholder;
        if (/^https?:\/\//i.test(v)) return v;
        return this.imageBase + v;
      },

      save: function () {
        var self = this;
        if (!self.current) return;
        if (self.currentKind === 'subtotal') { self.saveSubtotalForm(); return; }
        // зчитуємо icon з hidden filemanager input
        var inp = document.getElementById(self.iconInputId());
        if (inp) { self.current.params = self.current.params || {}; self.current.params.icon = inp.value; }
        self.busy = true;
        window.okPost(ctxUrls().cm_save, {
          method_id: self.current.method_id,
          payload:   JSON.stringify(self.current),
        }, function (d) {
          self.busy = false;
          if (d && d.success) {
            window.okNotify(tFn('js_saved'), 'success');
            self.load();
          } else {
            window.okNotify((d && d.message) || tFn('js_error'), 'error');
          }
        });
      },
      saveSubtotalForm: function () {
        var self = this;
        var s = self.current;
        self.busy = true;
        window.okPost(ctxUrls().cm_subtotal_save, {
          subtotal_id: s.subtotal_id,
          payload: JSON.stringify({
            sort_order:   s.sort_order || 0,
            status:       s.status ? 1 : 0,
            descriptions: s.descriptions,
            rules:        { value: s.rules.value || '', round: s.rules.round ? 1 : 0, conditions: s.conditions },
          }),
        }, function (d) {
          self.busy = false;
          window.okNotify((d && d.success) ? tFn('js_saved') : tFn('js_error'), (d && d.success) ? 'success' : 'error');
          if (d && d.success) self.load();
        });
      },
      langName: function (langId) {
        var l = (this.languages || []).find(function (x) { return x.language_id === langId; });
        return l ? l.name : langId;
      },

      // ── subtotals («Облік у замовленні») ──────────────────────────────
      normalizeSubtotal: function (s) {
        var self = this;
        s.rules = s.rules || {};
        if (typeof s.rules.value === 'undefined') s.rules.value = '';
        if (typeof s.rules.round === 'undefined') s.rules.round = 0;
        if (!s.rules.conditions || !Array.isArray(s.rules.conditions.rules)) {
          s.rules.conditions = { match: 'all', rules: [] };
        }
        s.descriptions = s.descriptions || {};
        (self.languages || []).forEach(function (l) {
          if (!s.descriptions[l.language_id]) s.descriptions[l.language_id] = { name: '' };
        });
        return s;
      },
      addSubtotal: function () {
        var self = this;
        window.okPost(ctxUrls().cm_subtotal_add, {}, function (d) {
          if (d && d.success) {
            self.load();
            // відкриваємо щойно створений на редагування
            setTimeout(function () {
              var fresh = (self.subtotals || []).find(function (x) { return x.subtotal_id === d.subtotal_id; });
              if (fresh) self.editSubtotal(fresh);
            }, 250);
          }
        });
      },
      delSubtotal: function (s) {
        var self = this;
        if (!confirm(tFn('cm_confirm_delete_subtotal'))) return;
        window.okPost(ctxUrls().cm_subtotal_delete, { subtotal_id: s.subtotal_id }, function (d) {
          if (d && d.success) {
            if (self.current && self.currentKind === 'subtotal' && self.current.subtotal_id === s.subtotal_id) self.current = null;
            self.load();
          }
        });
      },
      toggleSubtotal: function (s) {
        var self = this;
        s.status = s.status ? 0 : 1;
        window.okPost(ctxUrls().cm_subtotal_save, {
          subtotal_id: s.subtotal_id,
          payload: JSON.stringify({ sort_order: s.sort_order || 0, status: s.status, descriptions: s.descriptions, rules: s.rules }),
        }, function () {});
      },
      subItemName: function (s) {
        var d = s.descriptions && s.descriptions[this.activeLang];
        return (d && d.name) || ('#' + s.subtotal_id);
      },
    };
  };

  // ─── License info / activation ─────────────────────────────────────────
  window.okecLicense = function (init) {
    init = init || {};
    return {
      loading: false,
      activating: false,
      info: null,
      key:  init.key || '',
      t: tFn,
      prefill: function (info, key) {
        this.info = info || null;
        if (key) this.key = key;
      },
      load: function () {
        var self = this;
        var url = ctxUrls().license_info;
        if (!url) return;
        self.loading = true;
        fetch(url, { credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            self.loading = false;
            self.info = (data && data.info) || null;
            tickIcons();
          })
          .catch(function () { self.loading = false; });
      },
      activate: function () {
        var self = this;
        if (!self.key) return;
        self.activating = true;
        window.okPost(ctxUrls().license_activate, { license_key: self.key }, function (data) {
          self.activating = false;
          if (data && data.success) {
            window.okNotify((data && data.message) || tFn('license_activated'), 'success');
            self.info = data.info || self.info;
          } else {
            window.okNotify((data && data.message) || tFn('license_activate_failed'), 'error');
          }
        });
      },
    };
  };

  // ─── Reminder template preview (general settings tab) ──────────────────
  window.okecReminderPreview = function () {
    var langs = (window.OkEasycheckoutContext || {}).languages || [];
    return {
      lang: langs[0] ? langs[0].code : 'uk-ua',
      modalOpen: false,
      rendered: { subject: '', body: '' },
      t: tFn,
      open: function () {
        var subjEl = document.querySelector('[name="reminder_subject[' + this.lang + ']"]');
        var bodyEl = document.querySelector('[name="reminder_body[' + this.lang + ']"]');
        var subj   = subjEl ? subjEl.value : '';
        var body   = bodyEl ? bodyEl.value : '';
        var ctx    = window.OkEasycheckoutContext || {};
        var sample = {
          firstname:    'John',
          lastname:     'Doe',
          email:        'john@example.com',
          store_name:   ctx.store_name || '',
          recovery_url: (ctx.catalog_base_url || '') + 'index.php?route=checkout/easycheckout&recover=' + 'a'.repeat(32),
          total:        '199.99',
          currency:     'UAH',
        };
        var render = function (tpl) {
          return tpl.replace(/\{([a-z_]+)\}/gi, function (_, k) {
            return sample[k] !== undefined ? sample[k] : ('{' + k + '}');
          });
        };
        this.rendered  = { subject: render(subj), body: render(body) };
        this.modalOpen = true;
        if (window.lucide) setTimeout(function () { window.lucide.createIcons(); }, 50);
      },
    };
  };

  // ─── Integrations marketplace ─────────────────────────────────────────
  window.okecIntegrations = function () {
    return {
      loading: false,
      items: [],
      modalOpen: false,
      current: null,           // { code, name, schema }
      currentSettings: {},     // editable form state
      fieldPickerOpen: {},     // multitag dropdown state per field key
      busy: {},                // per-code busy flags (refresh/purge)
      market: [],
      busyMarket: {},
      loadingMarket: false,
      marketFilter: { q: '', country: '', category: '' },
      filteredMarket: function () {
        var f = this.marketFilter;
        var q = (f.q || '').toLowerCase();
        return (this.market || []).filter(function (m) {
          if (f.country && m.country !== f.country) return false;
          if (f.category && m.category !== f.category) return false;
          if (q && (m.name + ' ' + m.description).toLowerCase().indexOf(q) === -1) return false;
          return true;
        });
      },
      marketCountries: function () {
        var seen = {}; (this.market || []).forEach(function (m) { if (m.country) seen[m.country] = 1; });
        return Object.keys(seen).sort();
      },
      marketCategories: function () {
        var seen = {}; (this.market || []).forEach(function (m) { if (m.category) seen[m.category] = 1; });
        return Object.keys(seen).sort();
      },
      view: 'list',            // 'list' | 'settings'
      health: {},              // per-code health data shown on settings page
      t: tFn,
      schemaSections: function () {
        if (!this.current || !this.current.schema) return [];
        var groups = {};
        this.current.schema.forEach(function (f) {
          var s = (f.section && f.section.label) ? f.section : { key: 'general', label: tFn("integration_section_general_fallback") || "Загальні" };
          if (!groups[s.key]) groups[s.key] = { label: s.label, icon: s.icon || 'sliders', fields: [] };
          groups[s.key].fields.push(f);
        });
        return Object.values(groups);
      },
      backToList: function () { this.view = 'list'; this.current = null; tickIcons(); },

      iconUrl: function (code) {
        var u = ctxUrls().integration_icon;
        return u ? (u + '&code=' + encodeURIComponent(code)) : '';
      },
      load: function () {
        var self = this;
        var url = ctxUrls().integrations_list;
        if (!url) return;
        self.loading = true;
        fetch(url, { credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            self.loading = false;
            self.items = (data && data.items) || [];
            tickIcons();
          })
          .catch(function () { self.loading = false; });
      },
      loadMarket: function () {
        var self = this;
        var url = ctxUrls().marketplace_list;
        if (!url) return;
        self.loadingMarket = true;
        fetch(url, { credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            self.loadingMarket = false;
            self.market = (data && data.items) || [];
            tickIcons();
          })
          .catch(function () { self.loadingMarket = false; });
      },
      install: function (m) {
        var self = this;
        if (self.busyMarket[m.code]) return;
        if (!window.confirm(tFn('marketplace_install_confirm') || 'Install?')) return;
        self.busyMarket[m.code] = true;
        window.okPost(ctxUrls().marketplace_install, { code: m.code, url: m.download_url }, function (data) {
          self.busyMarket[m.code] = false;
          window.okNotify((data && data.message) || (data && data.success ? 'OK' : 'Failed'),
                          data && data.success ? 'success' : 'error');
          if (data && data.success) { self.loadMarket(); self.load(); }
        });
      },
      uninstall: function (m) {
        var self = this;
        if (self.busyMarket[m.code]) return;
        if (!window.confirm(tFn('marketplace_uninstall_confirm') || 'Uninstall?')) return;
        self.busyMarket[m.code] = true;
        window.okPost(ctxUrls().marketplace_uninstall, { code: m.code }, function (data) {
          self.busyMarket[m.code] = false;
          window.okNotify((data && data.message) || (data && data.success ? 'OK' : 'Failed'),
                          data && data.success ? 'success' : 'error');
          if (data && data.success) { self.loadMarket(); self.load(); }
        });
      },
      update: function (m) {
        var self = this;
        if (self.busyMarket[m.code]) return;
        self.busyMarket[m.code] = true;
        window.okPost(ctxUrls().marketplace_update, { code: m.code, url: m.download_url }, function (data) {
          self.busyMarket[m.code] = false;
          window.okNotify((data && data.message) || (data && data.success ? 'OK' : 'Failed'),
                          data && data.success ? 'success' : 'error');
          if (data && data.success) { self.loadMarket(); self.load(); }
        });
      },
      toggle: function (item) {
        var self = this;
        window.okPost(ctxUrls().integration_toggle, {
          code: item.code,
          enabled: item.enabled ? 0 : 1,
        }, function (data) {
          if (data && data.success) {
            item.enabled = data.enabled;
            window.okNotify(tFn('js_saved'), 'success');
          }
        });
      },
      openSettings: function (code) {
        var self = this;
        var url = ctxUrls().integration_get + '&code=' + encodeURIComponent(code);
        fetch(url, { credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (!data || !data.success) { window.okNotify(tFn('js_error'), 'error'); return; }
            // Pull listing meta for icon/country/enabled/version/has_icon
            var meta = (self.items || []).find(function (i) { return i.code === code; }) || {};
            self.current = {
              code: data.code, name: data.name, schema: data.schema || [],
              icon: meta.icon || 'puzzle', has_icon: !!meta.has_icon,
              enabled: !!meta.enabled, country: meta.country || '', version: meta.version || ''
            };
            self.currentSettings = Object.assign({}, data.settings || {});
            self.fieldPickerOpen = {};
            self.view = 'settings';
            self.loadHealth(code);
            setTimeout(tickIcons, 30);
          });
      },
      loadHealth: function (code) {
        var self = this;
        var url = ctxUrls().integration_health;
        if (!url) return;
        fetch(url + '&code=' + encodeURIComponent(code), { credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (data) { self.health = (data && data.health) || {}; });
      },
      saveSettings: function () {
        var self = this;
        if (!self.current) return;
        window.okPost(ctxUrls().integration_save, {
          code: self.current.code,
          settings: self.currentSettings,
        }, function (data) {
          if (data && data.success) {
            window.okNotify(tFn('js_saved'), 'success');
          } else {
            window.okNotify((data && data.message) || tFn('js_error'), 'error');
          }
        });
      },
      runAction: function (code, action) {
        var self = this;
        window.okPost(ctxUrls().integration_action, { code: code, action: action }, function (data) {
          window.okNotify((data && data.message) || (data && data.success ? 'OK' : 'Failed'),
                          data && data.success ? 'success' : 'error');
        });
      },
      refreshCache: function (item) {
        var self = this;
        if (self.busy[item.code]) return;
        self.busy[item.code] = true;
        window.okNotify(tFn('integration_refresh_running') || 'Refreshing...', 'info');
        window.okPost(ctxUrls().integration_refresh, { code: item.code }, function (data) {
          self.busy[item.code] = false;
          var msg = (data && data.message) || '';
          if (data && data.stats) {
            var parts = [];
            for (var k in data.stats) if (data.stats.hasOwnProperty(k)) parts.push(k + ': ' + data.stats[k]);
            if (parts.length) msg += ' (' + parts.join(', ') + ')';
          }
          window.okNotify(msg || (data && data.success ? 'OK' : 'Failed'),
                          data && data.success ? 'success' : 'error');
        });
      },
      purgeData: function (item) {
        var self = this;
        if (self.busy[item.code]) return;
        if (!window.confirm(tFn('integration_purge_confirm') || 'Purge cached data?')) return;
        self.busy[item.code] = true;
        window.okPost(ctxUrls().integration_purge, { code: item.code }, function (data) {
          self.busy[item.code] = false;
          window.okNotify((data && data.message) || (data && data.success ? 'OK' : 'Failed'),
                          data && data.success ? 'success' : 'error');
        });
      },
      installFields: function (item) {
        var self = this;
        if (self.busy[item.code]) return;
        self.busy[item.code] = true;
        window.okPost(ctxUrls().integration_install_fields, { code: item.code }, function (data) {
          self.busy[item.code] = false;
          window.okNotify((data && data.message) || (data && data.success ? 'OK' : 'Failed'),
                          data && data.success ? 'success' : 'error');
        });
      },
      addToLayout: function (item) {
        var self = this;
        if (self.busy[item.code]) return;
        self.busy[item.code] = true;
        window.okPost(ctxUrls().integration_add_to_layout, { code: item.code, page: 'checkout' }, function (data) {
          self.busy[item.code] = false;
          window.okNotify((data && data.message) || (data && data.success ? 'OK' : 'Failed'),
                          data && data.success ? 'success' : 'error');
        });
      },
      // Multiselect helpers (for ok-multitag inside settings modal)
      multiselectValue: function (field) {
        var v = this.currentSettings[field.key];
        if (v === undefined) v = field.default || [];
        if (!Array.isArray(v)) v = (v === '' || v == null) ? [] : String(v).split(',');
        return v;
      },
      multiselectHas: function (field, value) {
        return this.multiselectValue(field).indexOf(String(value)) !== -1
            || this.multiselectValue(field).indexOf(value) !== -1;
      },
      multiselectToggle: function (field, value) {
        var arr = this.multiselectValue(field).slice();
        var idx = arr.indexOf(value);
        if (idx === -1) idx = arr.indexOf(String(value));
        if (idx === -1) arr.push(value);
        else arr.splice(idx, 1);
        this.currentSettings[field.key] = arr;
      },
      multiselectRemove: function (field, value) {
        var arr = this.multiselectValue(field).filter(function (x) { return x !== value && String(x) !== String(value); });
        this.currentSettings[field.key] = arr;
      },
      multiselectOptionLabel: function (field, value) {
        var opt = (field.options || []).find(function (o) { return String(o.value) === String(value); });
        return opt ? opt.label : value;
      },
    };
  };
}());
