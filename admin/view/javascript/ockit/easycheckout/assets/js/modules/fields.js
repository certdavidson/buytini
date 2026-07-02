/**
 * EasyCheckout — Fields module (sub-state of okecApp)
 * © 2026 oc-kit.com | https://oc-kit.com
 *
 * Експортує `window.OkecFieldsModule.create(context, i18n, t)` що повертає
 * об'єкт-стан секції "Поля". Об'єкт додається в `okecApp().fields` і доступний
 * у twig через `fields.*`.
 */
(function () {
  'use strict';

  function buildEmptyDescriptions(languages) {
    var out = {};
    languages.forEach(function (lang) {
      out[lang.language_id] = { name: '', tooltip: '', placeholder: '' };
    });
    return out;
  }

  function buildEmptyForm(languages, fieldTypes) {
    var defaultType = (fieldTypes && fieldTypes.length) ? fieldTypes[0].code : 'text';
    var defaultBelongs = (fieldTypes && fieldTypes.length) ? fieldTypes[0].default_belongs_to : 'order';
    return {
      code: '',
      type: defaultType,
      belongs_to: defaultBelongs,
      mask_mode: 'manual',
      mask_value: '',
      default_mode: 'manual',
      default_value: '',
      save_to_comment: false,
      validation_rules: [],
      params: { options: [] },
      descriptions: buildEmptyDescriptions(languages)
    };
  }

  function ruleId() {
    return 'r' + Date.now().toString(36) + Math.random().toString(36).slice(2, 7);
  }

  function buildEmptyErrorText(languages) {
    var out = {};
    languages.forEach(function (lang) { out[lang.language_id] = ''; });
    return out;
  }

  function defaultRuleParams(type) {
    switch (type) {
      case 'length':  return { min: null, max: null };
      case 'regex':   return { pattern: '' };
      case 'api':     return { method: '' };
      case 'match':   return { field_code: '' };
      case 'not_empty':
      default:        return {};
    }
  }

  /**
   * Дефолтні type-specific параметри. Зберігаються поряд з options всередині form.params.
   * Кожен тип має свій ключ (consent, tel_intl, np, ...), щоб не змішувались.
   */
  function defaultTypeParams(type) {
    switch (type) {
      case 'consent':
        return {
          information_id: 0,
          information_title: '',     // дублюємо назву на момент збереження для UI
          version: '1',
          store_meta: false,
          custom_label: {}            // {language_id: "..."} — заповнюємо в openCreate з languages
        };
      case 'tel_intl':
        return { preferred_countries: 'UA,PL,US', default_country: 'UA' };
      case 'autocomplete_np':
        return { scope: 'warehouse' };
      case 'autocomplete_ukrposhta':
        return { scope: 'index' };
      case 'computed_hidden':
        return { source: 'utm_source', extra: '' };
      case 'group':
        return { columns: 1 };
      case 'country':
        return { restrict_to_iso: '' };
      case 'zone':
        return { depends_on_field: 'country' };
      case 'city':
        return { depends_on_field: 'zone' };
      case 'date':
        return {
          disable_past:    true,
          min_days_ahead:  0,
          max_days_ahead:  null,
          weekends:        []
        };
      case 'time':
        return {
          working_from:    '09:00',
          working_to:      '18:00',
          slot_minutes:    30,
          min_hours_ahead: 2,
          weekends:        [0, 6]   // нд, сб за замовчуванням
        };
      default:
        return {};
    }
  }

  /** Чи має тип додаткові type-specific параметри (для показу секції). */
  function hasTypeParams(type) {
    return [
      'consent', 'tel_intl', 'autocomplete_np', 'autocomplete_ukrposhta',
      'computed_hidden', 'group',
      'country', 'zone', 'city',
      'date', 'time'
    ].indexOf(type) !== -1;
  }

  window.OkecFieldsModule = {
    create: function (context, i18n, t) {
      var languages  = context.languages || [];
      var fieldTypes = context.field_types || [];
      var urls       = context.urls || {};

      // Усі мови мають бути в descriptions заздалегідь — модалка під x-show
      // обчислює x-model навіть коли закрита (як і field_modal).
      var emptyNativeDescs = function () {
        var d = {};
        languages.forEach(function (l) {
          d[l.language_id] = { name: '', placeholder: '', tooltip: '' };
        });
        return d;
      };

      return {
        // ─── State ──────────────────────────────────────────────────────
        _loaded: false,
        filter:  { search: '', type: '', belongs_to: '', usage: 'all' },
        items:    [],
        total:    0,
        loading:  false,
        selected: [],

        modal:  { open: false, editing: false, fieldId: 0 },
        form:   buildEmptyForm(languages, fieldTypes),
        currentTypeMeta: null,    // оновлюється у syncTypeMeta()
        useMask: false,           // toggle "Використовувати маску" в модалці
        useDefault: false,        // toggle "Задати значення за замовчуванням"
        // Consent autocomplete state (per-modal)
        consentSearch: { query: '', items: [], open: false, loading: false, timer: null },
        errors: {},
        saving: false,

        // ── Native (default OC) fields ──────────────────────────────────
        nativeItems: [],
        nativeModal: { open: false, fieldId: 0, code: '', belongs_to: '', type: '', defaultLabels: {} },
        nativeForm:  { descriptions: emptyNativeDescs() },

        /**
         * Inline rename — змінює name у primary-language description.
         * Робимо повний fieldGet → name update → fieldSave (інакше всі descriptions
         * втратять інші мови).
         */
        inlineRename: function (item, newName) {
          newName = (newName || '').trim();
          if (newName === '' || newName === this.primaryName(item)) return;
          var self = this;
          var primaryId = (context.languages && context.languages[0])
            ? context.languages[0].language_id : 1;

          fetch(urls.get + '&field_id=' + item.field_id, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
              if (!data || !data.success || !data.field) {
                window.okNotify(t('js_error'), 'error');
                return;
              }
              var f = data.field;
              if (!f.descriptions || typeof f.descriptions !== 'object') f.descriptions = {};
              if (!f.descriptions[primaryId]) f.descriptions[primaryId] = {};
              f.descriptions[primaryId].name = newName;

              // Save
              window.okPost(urls.save, {
                field_id:         item.field_id,
                code:             f.code,
                type:             f.type,
                belongs_to:       f.belongs_to,
                mask_mode:        f.mask_mode,
                mask_value:       f.mask_value || '',
                default_mode:     f.default_mode,
                default_value:    f.default_value || '',
                save_to_comment:  f.save_to_comment ? 1 : 0,
                validation_rules: JSON.stringify(f.validation_rules || []),
                params:           JSON.stringify(f.params || {}),
                descriptions:     JSON.stringify(f.descriptions || {}),
              }, function (resp) {
                if (resp && resp.success) {
                  window.okNotify(t('text_field_saved') || 'Saved', 'success');
                  self.load();
                } else {
                  window.okNotify((resp && resp.message) || t('js_error'), 'error');
                }
              });
            });
        },

        // ─── Native default fields (name/placeholder/tooltip overrides) ──
        loadNative: function () {
          var self = this;
          if (!urls.native_list) return;
          fetch(urls.native_list, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
              if (!d || !d.success) return;
              self.nativeItems = d.items || [];
              if (window.Alpine && window.lucide) {
                Alpine.nextTick(function () { window.lucide.createIcons(); });
              }
            });
        },
        nativePrimaryName: function (item) {
          var pid = (languages[0] || {}).language_id || 1;
          var ov = item.descriptions && item.descriptions[pid];
          if (ov && ov.name) return ov.name;
          return (item.default_labels && item.default_labels[pid]) || item.code;
        },
        nativeDefaultLabel: function (langId) {
          return (this.nativeModal.defaultLabels && this.nativeModal.defaultLabels[langId]) || '';
        },
        openNativeEdit: function (item) {
          var descs = {};
          languages.forEach(function (l) {
            var o = (item.descriptions && item.descriptions[l.language_id]) || {};
            descs[l.language_id] = {
              name:        o.name || '',
              placeholder: o.placeholder || '',
              tooltip:     o.tooltip || '',
            };
          });
          this.nativeForm  = { descriptions: descs };
          this.nativeModal = {
            open: true, fieldId: item.field_id, code: item.code,
            belongs_to: item.belongs_to, type: item.type,
            defaultLabels: item.default_labels || {},
          };
          if (window.Alpine && window.lucide) {
            Alpine.nextTick(function () { window.lucide.createIcons(); });
          }
        },
        closeNativeModal: function () { this.nativeModal.open = false; },
        saveNative: function () {
          var self = this;
          window.okPost(urls.native_save, {
            field_id:     self.nativeModal.fieldId,
            descriptions: JSON.stringify(self.nativeForm.descriptions),
          }, function (resp) {
            if (resp && resp.success) {
              window.okNotify(t('text_field_saved') || 'Saved', 'success');
              self.closeNativeModal();
              self.loadNative();
            } else {
              window.okNotify((resp && resp.message) || t('js_error'), 'error');
            }
          });
        },

        // ─── Helpers ────────────────────────────────────────────────────
        typeLabel: function (code) {
          var ft = fieldTypes.find(function (x) { return x.code === code; });
          return ft ? ft.label : code;
        },

        typeIcon: function (code) {
          var ft = fieldTypes.find(function (x) { return x.code === code; });
          return (ft && ft.icon) ? ft.icon : 'square';
        },

        /**
         * Оновлює currentTypeMeta згідно поточного form.type.
         * Викликається з openCreate/openEdit/onTypeChange — НЕ getter,
         * бо в Alpine 3 getter-и в nested-об'єктах не завжди реактивні
         * через Proxy-обгортку.
         */
        syncTypeMeta: function () {
          var t = this.form.type;
          this.currentTypeMeta = fieldTypes.find(function (x) { return x.code === t; }) || null;
        },

        /** При вимкненні toggle "Використовувати маску" — чистимо значення. */
        onUseMaskToggle: function () {
          if (!this.useMask) {
            this.form.mask_mode = 'manual';
            this.form.mask_value = '';
          }
        },

        /** Аналогічно для default value. */
        onUseDefaultToggle: function () {
          if (!this.useDefault) {
            this.form.default_mode = 'manual';
            this.form.default_value = '';
          }
        },

        // ─── Consent autocomplete ───────────────────────────────────────
        consentSearchInput: function (q) {
          var self = this;
          self.consentSearch.query = q;
          self.consentSearch.open = q.length > 0;
          if (self.consentSearch.timer) clearTimeout(self.consentSearch.timer);
          self.consentSearch.timer = setTimeout(function () {
            self.consentSearchFetch();
          }, 250);
        },

        consentSearchFetch: function () {
          var self = this;
          var q = self.consentSearch.query;
          if (!q) {
            self.consentSearch.items = [];
            self.consentSearch.loading = false;
            return;
          }
          self.consentSearch.loading = true;
          fetch(urls.info_search + '&q=' + encodeURIComponent(q), { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
              self.consentSearch.items = (data && data.items) || [];
              self.consentSearch.loading = false;
            })
            .catch(function () {
              self.consentSearch.loading = false;
            });
        },

        consentPick: function (item) {
          if (!this.form.params.consent) this.form.params.consent = defaultTypeParams('consent');
          this.form.params.consent.information_id = item.information_id;
          this.form.params.consent.information_title = item.title;
          this.consentSearch.query = item.title;
          this.consentSearch.open = false;
        },

        consentClear: function () {
          if (!this.form.params.consent) return;
          this.form.params.consent.information_id = 0;
          this.form.params.consent.information_title = '';
          this.consentSearch.query = '';
          this.consentSearch.items = [];
        },

        /** Гідрація consent при відкритті редагування (підтягує title по id). */
        consentHydrate: function () {
          var self = this;
          var id = self.form.params && self.form.params.consent && self.form.params.consent.information_id;
          if (!id) {
            self.consentSearch.query = '';
            return;
          }
          fetch(urls.info_search + '&id=' + id, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
              if (data && data.items && data.items.length) {
                self.form.params.consent.information_title = data.items[0].title;
                self.consentSearch.query = data.items[0].title;
              }
            });
        },

        // ─── Weekends multi-toggle ──────────────────────────────────────
        toggleWeekend: function (bucket, day) {
          if (!Array.isArray(this.form.params[bucket].weekends)) {
            this.form.params[bucket].weekends = [];
          }
          var arr = this.form.params[bucket].weekends;
          var idx = arr.indexOf(day);
          if (idx === -1) arr.push(day);
          else arr.splice(idx, 1);
        },

        isWeekend: function (bucket, day) {
          var ws = this.form.params[bucket] && this.form.params[bucket].weekends;
          return Array.isArray(ws) && ws.indexOf(day) !== -1;
        },

        primaryName: function (item) {
          if (!item.descriptions) return '';
          for (var i = 0; i < languages.length; i++) {
            var d = item.descriptions[languages[i].language_id];
            if (d && d.name) return d.name;
          }
          var keys = Object.keys(item.descriptions);
          for (var j = 0; j < keys.length; j++) {
            if (item.descriptions[keys[j]].name) return item.descriptions[keys[j]].name;
          }
          return '—';
        },

        formatDate: function (d) {
          if (!d) return '';
          return d.replace('T', ' ').slice(0, 16);
        },

        errorText: function (key) {
          var code = this.errors[key];
          if (!code) return '';
          var map = {
            required:                 'error_field_code_required',
            invalid_format:           'error_field_code_format',
            duplicate:                'error_field_code_duplicate',
            reserved:                 'error_field_code_reserved',
            invalid:                  'error_field_type_invalid',
            required_in_any_language: 'error_field_name_required'
          };
          return t(map[code] || code);
        },

        /** Чи є хоча б одна помилка валідації (окрім _global). */
        hasFieldErrors: function () {
          for (var k in this.errors) {
            if (k !== '_global' && this.errors[k]) return true;
          }
          return false;
        },

        /** Копія errors без _global — для рендеру списку у банері. */
        fieldErrors: function () {
          var out = {};
          for (var k in this.errors) {
            if (k !== '_global' && this.errors[k]) out[k] = this.errors[k];
          }
          return out;
        },

        /** Людська назва поля у банері помилок. */
        errorFieldLabel: function (key) {
          var map = {
            code:         t('entry_field_code'),
            type:         t('entry_field_type'),
            belongs_to:   t('entry_field_belongs_to'),
            name:         t('entry_field_name'),
            mask_mode:    t('entry_field_mask_mode'),
            default_mode: t('entry_field_default_mode')
          };
          return map[key] || key;
        },

        /** Виклик з шаблону для t() — щоб не дублювати i18n у twig. */
        t: function (k) { return t(k); },

        // ─── Local code generator (синхронно, без AJAX) ─────────────────
        // Дивимось серед уже завантажених `items` найбільший суфікс fieldN
        // та повертаємо field(N+1).
        nextLocalCode: function () {
          var taken = {};
          this.items.forEach(function (item) {
            var m = /^field(\d+)$/.exec(item.code);
            if (m) taken[parseInt(m[1], 10)] = true;
          });
          var n = 1;
          while (taken[n]) n++;
          return 'field' + n;
        },

        // ─── Load list ──────────────────────────────────────────────────
        load: function () {
          var self = this;
          self.loading = true;
          var q = new URLSearchParams({
            search:     self.filter.search || '',
            type:       self.filter.type || '',
            belongs_to: self.filter.belongs_to || '',
            usage:      self.filter.usage || 'all'
          }).toString();

          fetch(urls.list + '&' + q, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
              self.items    = data.items || [];
              self.total    = data.total || 0;
              self.selected = [];
              self.loading  = false;

              // Перерендер іконок таблиці + sortable
              if (window.Alpine && window.lucide) {
                Alpine.nextTick(function () {
                  window.lucide.createIcons();
                  self._initSortable();
                });
              }
            })
            .catch(function () {
              self.loading = false;
              window.okNotify(t('js_network_error'), 'error');
            });
        },

        _sortableInstance: null,
        _initSortable: function () {
          var tbody = document.querySelector('[data-okec-sortable-fields]');
          if (!tbody || !window.Sortable) return;
          if (this._sortableInstance) { try { this._sortableInstance.destroy(); } catch (e) {} }
          this._sortableInstance = new window.Sortable(tbody, {
            animation: 150,
            handle: '.okec-drag-handle',
            ghostClass: 'okec-row-dragging',
            onEnd: function () {
              var order = [];
              tbody.querySelectorAll('tr[data-field-id]').forEach(function (tr, idx) {
                order.push({ field_id: parseInt(tr.dataset.fieldId, 10), sort_order: idx });
              });
              var body = new URLSearchParams();
              order.forEach(function (o, i) {
                body.append('order[' + i + '][field_id]',   o.field_id);
                body.append('order[' + i + '][sort_order]', o.sort_order);
              });
              fetch(urls.fields_reorder, {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body
              }).then(function () { window.okNotify(t('text_field_saved') || 'Saved', 'success'); });
            }
          });
        },

        toggleAll: function (checked) {
          this.selected = checked ? this.items.map(function (i) { return i.field_id; }) : [];
        },

        // ─── Modal: create/edit ─────────────────────────────────────────
        openCreate: function () {
          var self = this;
          self.errors = {};
          self.form = buildEmptyForm(languages, fieldTypes);
          self.modal.editing = false;
          self.modal.fieldId = 0;
          self.modal.open = true;
          self.useMask = false;
          self.useDefault = false;
          self.syncTypeMeta();

          // 1) Префіл локально (sync) — щоб поле ніколи не було порожнім.
          self.form.code = self.nextLocalCode();

          // 2) Перевіряємо на сервері (на випадок якщо є поля поза сторінкою).
          fetch(urls.next_code, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
              if (data && data.code && self.form.code === self.nextLocalCode()) {
                // Замінюємо тільки якщо користувач ще не редагував поле.
                self.form.code = data.code;
              }
            })
            .catch(function () { /* ignore — fallback залишається */ });

          if (window.Alpine && window.lucide) {
            Alpine.nextTick(function () { window.lucide.createIcons(); });
          }
        },

        openEdit: function (fieldId) {
          var self = this;
          self.errors = {};
          self.modal.editing = true;
          self.modal.fieldId = fieldId;
          self.modal.open = true;

          fetch(urls.get + '&field_id=' + fieldId, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
              if (!data.success || !data.field) {
                self.closeModal();
                return;
              }
              var f = data.field;
              var descriptions = buildEmptyDescriptions(languages);
              languages.forEach(function (lang) {
                var d = (f.descriptions && f.descriptions[lang.language_id]) || {};
                descriptions[lang.language_id] = {
                  name:        d.name || '',
                  tooltip:     d.tooltip || '',
                  placeholder: d.placeholder || ''
                };
              });
              var params = f.params || {};
              if (!Array.isArray(params.options)) params.options = [];

              self.form = {
                code:             f.code,
                type:             f.type,
                belongs_to:       f.belongs_to,
                mask_mode:        f.mask_mode || 'manual',
                mask_value:       f.mask_value || '',
                default_mode:     f.default_mode || 'manual',
                default_value:    f.default_value || '',
                save_to_comment:  !!f.save_to_comment,
                validation_rules: Array.isArray(f.validation_rules) ? f.validation_rules : [],
                params:           params,
                descriptions:     descriptions
              };
              // Toggle-и підказують чи показувати секції в модалці.
              self.useMask    = !!self.form.mask_value    || self.form.mask_mode    === 'api';
              self.useDefault = !!self.form.default_value || self.form.default_mode === 'api';
              self.consentSearch = { query: '', items: [], open: false, loading: false, timer: null };
              if (self.form.type === 'consent') self.consentHydrate();
              self.syncTypeMeta();
              self.migrateOptionsLabels();

              if (window.Alpine && window.lucide) {
                Alpine.nextTick(function () { window.lucide.createIcons(); });
              }
            });
        },

        closeModal: function () {
          this.modal.open = false;
          this.errors = {};
        },

        hasTypeParams: function (type) {
          return hasTypeParams(type);
        },

        onTypeChange: function () {
          this.syncTypeMeta();
          var meta = this.currentTypeMeta;
          if (!meta) return;
          // options
          if (!meta.has_options) {
            this.form.params.options = [];
          } else if (!Array.isArray(this.form.params.options)) {
            this.form.params.options = [];
          }
          // type-specific params bucket
          var bucketKey = this.form.type;
          if (hasTypeParams(bucketKey) && !this.form.params[bucketKey]) {
            this.form.params[bucketKey] = defaultTypeParams(bucketKey);
          }
          // belongs_to (only on create)
          if (!this.modal.editing && meta.default_belongs_to) {
            this.form.belongs_to = meta.default_belongs_to;
          }
        },

        // ─── JSON Import (тригерить hidden file-input) ─────────────
        openImport: function () {
          var self = this;
          var input = document.createElement('input');
          input.type = 'file';
          input.accept = 'application/json,.json';
          input.onchange = function () {
            if (!input.files || !input.files[0]) return;
            var fd = new FormData();
            fd.append('file', input.files[0]);
            fetch(urls.fields_import, { method: 'POST', credentials: 'same-origin', body: fd })
              .then(function (r) { return r.json(); })
              .then(function (data) {
                if (data && data.success) {
                  var msg = (t('text_fields_imported') || 'Imported %d fields, skipped %s')
                            .replace('%d', data.created || 0)
                            .replace('%s', data.skipped || 0);
                  window.okNotify(msg, 'success');
                  self.load();
                } else {
                  window.okNotify((data && data.message) || t('js_error'), 'error');
                }
              })
              .catch(function () { window.okNotify(t('js_network_error'), 'error'); });
          };
          input.click();
        },

        // ─── Presets ──────────────────────────────────────────────
        presets: [],

        loadPresets: function () {
          if (this.presets.length) return; // вже завантажено
          var self = this;
          fetch(urls.presets_list, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
              if (data && data.success) self.presets = data.items || [];
              if (window.lucide) window.lucide.createIcons();
            });
        },

        applyPreset: function (code) {
          if (!code) return;
          if (!confirm(t('text_apply_preset_confirm') || 'Apply preset?')) return;
          var self = this;
          window.okPost(urls.preset_apply, { preset: code }, function (data) {
            if (data && data.success) {
              window.okNotify(data.message || 'OK', 'success');
              self.load(); // re-fetch fields list
            } else {
              window.okNotify((data && data.message) || t('js_error'), 'error');
            }
          }, function () {
            window.okNotify(t('js_network_error'), 'error');
          });
        },

        addOption: function () {
          if (!Array.isArray(this.form.params.options)) this.form.params.options = [];
          // Multilang labels — порожні рядки на кожну мову з context.languages
          var labels = {};
          (context.languages || []).forEach(function (lang) {
            labels[lang.language_id] = '';
          });
          this.form.params.options.push({ value: '', labels: labels });
        },

        removeOption: function (idx) {
          this.form.params.options.splice(idx, 1);
        },

        // ─── Bulk import options ───────────────────────────────────
        optionsImport: { open: false, text: '', replace: false },

        applyBulkImport: function () {
          var text = (this.optionsImport.text || '').trim();
          if (!text) { this.optionsImport.open = false; return; }

          var langs = context.languages || [];
          var primaryLangId = langs.length ? langs[0].language_id : 1;

          var parsed = text.split(/\r?\n/).map(function (line) {
            line = line.trim();
            if (!line) return null;
            // CSV parse: підтримує quoted-strings зі коми всередині
            var cells = [];
            var current = '';
            var inQuote = false;
            for (var i = 0; i < line.length; i++) {
              var c = line[i];
              if (c === '"' && (i === 0 || line[i - 1] !== '\\')) { inQuote = !inQuote; continue; }
              if (c === ',' && !inQuote) { cells.push(current.trim()); current = ''; continue; }
              current += c;
            }
            cells.push(current.trim());
            return cells;
          }).filter(Boolean);

          if (!parsed.length) { this.optionsImport.open = false; return; }

          var newOptions = parsed.map(function (cells) {
            var labels = {};
            langs.forEach(function (lang, idx) {
              labels[lang.language_id] = (cells[idx + 1] || '').trim();
            });
            // Якщо лише value (одна колонка) — використовуємо value як label primary мови
            if (Object.values(labels).every(function (v) { return v === ''; })) {
              labels[primaryLangId] = cells[0] || '';
            }
            return { value: (cells[0] || '').trim(), labels: labels };
          });

          if (this.optionsImport.replace) {
            this.form.params.options = newOptions;
          } else {
            if (!Array.isArray(this.form.params.options)) this.form.params.options = [];
            this.form.params.options = this.form.params.options.concat(newOptions);
          }
          this.optionsImport = { open: false, text: '', replace: false };
        },

        /** Backfill labels-object для legacy options що мали flat `label`. */
        migrateOptionsLabels: function () {
          if (!Array.isArray(this.form.params && this.form.params.options)) return;
          var primaryLangId = (context.languages && context.languages[0])
            ? context.languages[0].language_id : 1;
          this.form.params.options.forEach(function (opt) {
            if (!opt.labels || typeof opt.labels !== 'object' || Array.isArray(opt.labels)) {
              var labels = {};
              (context.languages || []).forEach(function (lang) {
                labels[lang.language_id] = '';
              });
              // Зберегти legacy `label` як значення для primary мови
              if (typeof opt.label === 'string' && opt.label !== '') {
                labels[primaryLangId] = opt.label;
              }
              opt.labels = labels;
            } else {
              // Догенеровуємо ключі для нових мов
              (context.languages || []).forEach(function (lang) {
                if (typeof opt.labels[lang.language_id] === 'undefined') {
                  opt.labels[lang.language_id] = '';
                }
              });
            }
          });
        },

        // ─── Validation rules ───────────────────────────────────────────
        ruleTypes: ['not_empty', 'length', 'regex', 'api', 'match'],

        ruleLabel: function (type) {
          return t('rule_type_' + type);
        },

        addRule: function (type) {
          if (!Array.isArray(this.form.validation_rules)) this.form.validation_rules = [];
          this.form.validation_rules.push({
            id:         ruleId(),
            type:       type,
            params:     defaultRuleParams(type),
            error_text: buildEmptyErrorText(languages)
          });
          if (window.Alpine && window.lucide) {
            Alpine.nextTick(function () { window.lucide.createIcons(); });
          }
        },

        removeRule: function (idx) {
          this.form.validation_rules.splice(idx, 1);
        },

        // ─── IMask preview ──────────────────────────────────────────────
        // Викликається з x-effect, коли змінюється mask_value або mask_mode.
        applyMaskPreview: function (el) {
          if (!el) return;
          // Знищити попередній instance
          if (el._imask) { el._imask.destroy(); el._imask = null; el.value = ''; }
          if (this.form.mask_mode !== 'manual')           return;
          if (!window.IMask)                              return;
          var pattern = (this.form.mask_value || '').trim();
          if (!pattern)                                    return;
          try {
            // Простий evristик: якщо patters містить тільки 0/9/підставні — pattern mask;
            // інакше — regex.
            var maskOpts;
            if (/^[\s+\-_().,/0-9aA*?#]+$/.test(pattern)) {
              maskOpts = { mask: pattern.replace(/9/g, '0') };
            } else {
              // Як regex (з лапок або без)
              var re = pattern.replace(/^\/|\/[gimsu]*$/g, '');
              maskOpts = { mask: new RegExp(re) };
            }
            el._imask = window.IMask(el, maskOpts);
          } catch (e) {
            // ignore — невалідна маска
          }
        },

        // ─── Save ───────────────────────────────────────────────────────
        save: function () {
          var self = this;
          self.errors = {};
          self.saving = true;

          var payload = {
            field_id:         self.modal.fieldId,
            code:             self.form.code,
            type:             self.form.type,
            belongs_to:       self.form.belongs_to,
            mask_mode:        self.form.mask_mode,
            mask_value:       self.form.mask_value,
            default_mode:     self.form.default_mode,
            default_value:    self.form.default_value,
            save_to_comment:  self.form.save_to_comment ? 1 : 0,
            descriptions:     JSON.stringify(self.form.descriptions),
            validation_rules: JSON.stringify(self.form.validation_rules || []),
            params:           JSON.stringify(self.form.params || {})
          };

          window.okPost(
            urls.save,
            payload,
            function (data) {
              self.saving = false;
              if (data.success) {
                window.okNotify(data.message || t('text_field_saved'), 'success');
                self.closeModal();
                self.load();
              } else {
                self.errors = data.errors || {};
                if (data.message) self.errors._global = data.message;
                window.okNotify(data.message || t('js_error'), 'error');
                // Скролимо модалку до банера зверху, щоб користувач побачив помилки
                setTimeout(function () {
                  var body = document.querySelector('.okec-modal__body');
                  if (body) body.scrollTop = 0;
                }, 50);
              }
            },
            function () {
              self.saving = false;
              window.okNotify(t('js_network_error'), 'error');
            }
          );
        },

        /**
         * Slugify primary-language name → field code.
         * Cyrillic transliteration + lowercase + dash-separated (a-z 0-9 _ -).
         */
        regenerateCodeFromName: function () {
          var langs = context.languages || [];
          if (!langs.length) return;
          var primaryId = langs[0].language_id;
          var name = (this.form.descriptions && this.form.descriptions[primaryId]
                     && this.form.descriptions[primaryId].name) || '';
          name = name.trim();
          if (!name) {
            window.okNotify(t('text_field_name_empty') || 'Enter name first', 'warning');
            return;
          }

          // Cyrillic → Latin (basic UA/RU transliteration)
          var translit = {
            'а':'a','б':'b','в':'v','г':'h','ґ':'g','д':'d','е':'e','є':'ie',
            'ж':'zh','з':'z','и':'y','і':'i','ї':'i','й':'i','к':'k','л':'l',
            'м':'m','н':'n','о':'o','п':'p','р':'r','с':'s','т':'t','у':'u',
            'ф':'f','х':'kh','ц':'ts','ч':'ch','ш':'sh','щ':'shch','ь':'',
            'ю':'iu','я':'ia','ы':'y','э':'e','ё':'e','ъ':''
          };
          var slug = name.toLowerCase().split('').map(function (c) {
            return translit[c] !== undefined ? translit[c] : c;
          }).join('');
          // Усе крім a-z 0-9 заміняємо на `_`, схлопуємо повтори, тримаємо
          slug = slug.replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
          if (!slug) {
            window.okNotify(t('text_field_name_unsupported') || 'Cannot derive code', 'warning');
            return;
          }
          // Prepend short type prefix to avoid collisions across blocks/sets.
          // E.g. text+"phone" → "txt_phone", select+"city" → "sel_city".
          var typePrefix = ({
            text: 'txt', textarea: 'ta', select: 'sel', radio: 'rad',
            checkbox: 'chk', date: 'dt', hidden: 'hid', html: 'html',
            consent: 'cs', tel_intl: 'tel', autocomplete_np: 'np',
            autocomplete_ukrposhta: 'up', country: 'cnt', zone: 'zn', city: 'cty',
            time: 'tm', computed_hidden: 'chid', group: 'grp',
            address_select: 'addr', file: 'file', segmented: 'seg',
          })[this.form.type] || (this.form.type || 'fld').slice(0, 4);
          var prefixed = typePrefix + '_' + slug;
          this.form.code = prefixed.substring(0, 64);
        },

        // ─── Clone ──────────────────────────────────────────────────────
        cloneField: function (fieldId) {
          if (!fieldId) return;
          var self = this;
          window.okPost(urls.clone, { field_id: fieldId }, function (data) {
            if (data && data.success && data.field_id) {
              window.okNotify(t('text_field_cloned') || 'Cloned', 'success');
              self.load();
              // Опціонально — можна одразу відкрити edit нового поля
              // self.openEdit(data.field_id);
            } else {
              window.okNotify(t('js_error'), 'error');
            }
          }, function () {
            window.okNotify(t('js_network_error'), 'error');
          });
        },

        // ─── Delete ─────────────────────────────────────────────────────
        confirmDelete: function (fieldId) {
          if (!confirm(t('text_confirm_delete_field'))) return;
          this._performDelete(fieldId, false);
        },

        _performDelete: function (fieldId, force) {
          var self = this;
          window.okPost(urls.del, { field_id: fieldId, force: force ? 1 : 0 }, function (data) {
            if (data && data.success) {
              window.okNotify(t('text_field_deleted'), 'success');
              self.load();
              return;
            }
            // In-use → друге підтвердження
            if (data && data.in_use) {
              var msg = (t('text_field_in_use') || 'Field used in %d block(s). Force delete?')
                        .replace('%d', data.usage_count || 0);
              if (confirm(msg)) self._performDelete(fieldId, true);
              return;
            }
            window.okNotify(t('js_error'), 'error');
          });
        },

        bulkDelete: function () {
          if (!this.selected.length) return;
          if (!confirm(t('text_confirm_delete_fields'))) return;
          this._performBulkDelete(false);
        },

        // ─── Bulk edit ────────────────────────────────────────────────
        bulkEditOpen: false,
        bulkEdit: { belongs_to: '', save_to_comment: '' },

        openBulkEdit: function () {
          if (!this.selected.length) return;
          this.bulkEdit = { belongs_to: '', save_to_comment: '' };
          this.bulkEditOpen = true;
          if (window.Alpine && window.lucide) {
            Alpine.nextTick(function () { window.lucide.createIcons(); });
          }
        },
        closeBulkEdit: function () { this.bulkEditOpen = false; },
        applyBulkEdit: function () {
          var self = this;
          var changes = {};
          if (self.bulkEdit.belongs_to)             changes.belongs_to = self.bulkEdit.belongs_to;
          if (self.bulkEdit.save_to_comment !== '') changes.save_to_comment = parseInt(self.bulkEdit.save_to_comment, 10);
          if (!Object.keys(changes).length) {
            self.closeBulkEdit();
            return;
          }
          var body = new URLSearchParams();
          self.selected.forEach(function (id) { body.append('field_ids[]', id); });
          Object.keys(changes).forEach(function (k) { body.append('changes[' + k + ']', changes[k]); });
          fetch(urls.fields_bulk_edit, {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body
          })
            .then(function (r) { return r.json(); })
            .then(function (data) {
              if (data && data.success) {
                window.okNotify(data.message || t('js_saved'), 'success');
                self.closeBulkEdit();
                self.selected = [];
                self.load();
              } else {
                window.okNotify((data && data.message) || t('js_error'), 'error');
              }
            })
            .catch(function () { window.okNotify(t('js_network_error'), 'error'); });
        },

        _performBulkDelete: function (force) {
          var self = this;
          var body = new URLSearchParams();
          self.selected.forEach(function (id) { body.append('field_ids[]', id); });
          if (force) body.append('force', '1');

          fetch(urls.del_many, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body
          })
            .then(function (r) { return r.json(); })
            .then(function (data) {
              if (data && data.success) {
                window.okNotify(t('text_field_deleted'), 'success');
                self.selected = [];
                self.load();
                return;
              }
              if (data && data.in_use) {
                var fieldCount = Object.keys(data.usages_by_field || {}).length;
                var msg = (t('text_fields_in_use') || '%d fields are in use. Force delete all?')
                          .replace('%d', fieldCount);
                if (confirm(msg)) self._performBulkDelete(true);
              }
            })
            .catch(function () {
              window.okNotify(t('js_network_error'), 'error');
            });
        }
      };
    }
  };
}());
