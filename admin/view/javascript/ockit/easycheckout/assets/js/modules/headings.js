/**
 * EasyCheckout — Headings module (sub-state of okecApp)
 * © 2026 oc-kit.com | https://oc-kit.com
 */
(function () {
  'use strict';

  function buildEmptyDescriptions(languages) {
    var out = {};
    languages.forEach(function (lang) {
      out[lang.language_id] = { text: '' };
    });
    return out;
  }

  function buildEmptyForm(languages) {
    return {
      code: '',
      tag: 'h3',
      descriptions: buildEmptyDescriptions(languages)
    };
  }

  window.OkecHeadingsModule = {
    create: function (context, i18n, t) {
      var languages = context.languages || [];
      var urls      = context.urls || {};

      return {
        _loaded: false,
        filter:  { search: '', tag: '' },
        items:    [],
        total:    0,
        loading:  false,
        selected: [],

        modal:  { open: false, editing: false, headingId: 0 },
        form:   buildEmptyForm(languages),
        errors: {},
        saving: false,

        /** Inline tag change — fetch+save щоб не втратити descriptions. */
        inlineTagChange: function (item, newTag) {
          if (!newTag || newTag === item.tag) return;
          var self = this;
          fetch(urls.h_get + '&heading_id=' + item.heading_id, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
              if (!data || !data.success || !data.heading) return;
              var h = data.heading;
              window.okPost(urls.h_save, {
                heading_id:   item.heading_id,
                code:         h.code,
                tag:          newTag,
                descriptions: JSON.stringify(h.descriptions || {}),
              }, function (resp) {
                if (resp && resp.success) {
                  item.tag = newTag;
                  window.okNotify(t('text_heading_saved'), 'success');
                } else {
                  window.okNotify(t('js_error'), 'error');
                }
              });
            });
        },

        /** Inline rename — primary-language text. Fetch+save для збереження інших мов. */
        inlineRename: function (item, newText) {
          newText = (newText || '').trim();
          if (newText === '' || newText === this.primaryText(item)) return;
          var self = this;
          var primaryId = (context.languages && context.languages[0])
            ? context.languages[0].language_id : 1;

          fetch(urls.h_get + '&heading_id=' + item.heading_id, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
              if (!data || !data.success || !data.heading) {
                window.okNotify(t('js_error'), 'error');
                return;
              }
              var h = data.heading;
              if (!h.descriptions || typeof h.descriptions !== 'object') h.descriptions = {};
              if (!h.descriptions[primaryId]) h.descriptions[primaryId] = {};
              h.descriptions[primaryId].text = newText;

              window.okPost(urls.h_save, {
                heading_id:   item.heading_id,
                code:         h.code,
                tag:          h.tag,
                descriptions: JSON.stringify(h.descriptions),
              }, function (resp) {
                if (resp && resp.success) {
                  window.okNotify(t('text_heading_saved') || 'Saved', 'success');
                  self.load();
                } else {
                  window.okNotify((resp && resp.message) || t('js_error'), 'error');
                }
              });
            });
        },

        primaryText: function (item) {
          if (!item.descriptions) return '';
          for (var i = 0; i < languages.length; i++) {
            var d = item.descriptions[languages[i].language_id];
            if (d && d.text) return d.text;
          }
          var keys = Object.keys(item.descriptions);
          for (var j = 0; j < keys.length; j++) {
            if (item.descriptions[keys[j]].text) return item.descriptions[keys[j]].text;
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
            invalid:                  'error_field_type_invalid',
            required_in_any_language: 'error_heading_text_required'
          };
          return t(map[code] || code);
        },

        nextLocalCode: function () {
          var taken = {};
          this.items.forEach(function (item) {
            var m = /^heading(\d+)$/.exec(item.code);
            if (m) taken[parseInt(m[1], 10)] = true;
          });
          var n = 1;
          while (taken[n]) n++;
          return 'heading' + n;
        },

        load: function () {
          var self = this;
          self.loading = true;
          var q = new URLSearchParams({
            search: self.filter.search || '',
            tag:    self.filter.tag || ''
          }).toString();

          fetch(urls.h_list + '&' + q, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
              self.items    = data.items || [];
              self.total    = data.total || 0;
              self.selected = [];
              self.loading  = false;
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
          var tbody = document.querySelector('[data-okec-sortable-headings]');
          if (!tbody || !window.Sortable) return;
          if (this._sortableInstance) { try { this._sortableInstance.destroy(); } catch (e) {} }
          this._sortableInstance = new window.Sortable(tbody, {
            animation: 150,
            handle: '.okec-drag-handle',
            ghostClass: 'okec-row-dragging',
            onEnd: function () {
              var order = [];
              tbody.querySelectorAll('tr[data-heading-id]').forEach(function (tr, idx) {
                order.push({ heading_id: parseInt(tr.dataset.headingId, 10), sort_order: idx });
              });
              var body = new URLSearchParams();
              order.forEach(function (o, i) {
                body.append('order[' + i + '][heading_id]', o.heading_id);
                body.append('order[' + i + '][sort_order]', o.sort_order);
              });
              fetch(urls.h_reorder, {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body
              }).then(function () { window.okNotify(t('text_heading_saved') || 'Saved', 'success'); });
            }
          });
        },

        toggleAll: function (checked) {
          this.selected = checked ? this.items.map(function (i) { return i.heading_id; }) : [];
        },

        openCreate: function () {
          var self = this;
          self.errors = {};
          self.form = buildEmptyForm(languages);
          self.modal.editing = false;
          self.modal.headingId = 0;
          self.modal.open = true;
          self.form.code = self.nextLocalCode();

          fetch(urls.h_next_code, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
              if (data && data.code && self.form.code === self.nextLocalCode()) {
                self.form.code = data.code;
              }
            })
            .catch(function () {});

          if (window.Alpine && window.lucide) {
            Alpine.nextTick(function () { window.lucide.createIcons(); });
          }
        },

        openEdit: function (headingId) {
          var self = this;
          self.errors = {};
          self.modal.editing = true;
          self.modal.headingId = headingId;
          self.modal.open = true;

          fetch(urls.h_get + '&heading_id=' + headingId, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
              if (!data.success || !data.heading) {
                self.closeModal();
                return;
              }
              var h = data.heading;
              var descriptions = buildEmptyDescriptions(languages);
              languages.forEach(function (lang) {
                var d = (h.descriptions && h.descriptions[lang.language_id]) || {};
                descriptions[lang.language_id] = { text: d.text || '' };
              });
              self.form = {
                code: h.code,
                tag:  h.tag,
                descriptions: descriptions
              };
              if (window.Alpine && window.lucide) {
                Alpine.nextTick(function () { window.lucide.createIcons(); });
              }
            });
        },

        closeModal: function () {
          this.modal.open = false;
          this.errors = {};
        },

        save: function () {
          var self = this;
          self.errors = {};
          self.saving = true;

          var payload = {
            heading_id:   self.modal.headingId,
            code:         self.form.code,
            tag:          self.form.tag,
            descriptions: JSON.stringify(self.form.descriptions)
          };

          window.okPost(
            urls.h_save,
            payload,
            function (data) {
              self.saving = false;
              if (data.success) {
                window.okNotify(data.message || t('text_heading_saved'), 'success');
                self.closeModal();
                self.load();
              } else {
                self.errors = data.errors || {};
                if (data.message) self.errors._global = data.message;
                window.okNotify(data.message || t('js_error'), 'error');
              }
            },
            function () {
              self.saving = false;
              window.okNotify(t('js_network_error'), 'error');
            }
          );
        },

        openImport: function () {
          var self = this;
          var input = document.createElement('input');
          input.type = 'file';
          input.accept = 'application/json,.json';
          input.onchange = function () {
            if (!input.files || !input.files[0]) return;
            var fd = new FormData();
            fd.append('file', input.files[0]);
            fetch(urls.h_import, { method: 'POST', credentials: 'same-origin', body: fd })
              .then(function (r) { return r.json(); })
              .then(function (data) {
                if (data && data.success) {
                  var msg = (t('text_fields_imported') || 'Imported %d, skipped %s')
                            .replace('%d', data.created || 0).replace('%s', data.skipped || 0);
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

        cloneHeading: function (headingId) {
          if (!headingId) return;
          var self = this;
          window.okPost(urls.h_clone, { heading_id: headingId }, function (data) {
            if (data && data.success && data.heading_id) {
              window.okNotify(t('text_heading_cloned') || 'Cloned', 'success');
              self.load();
            } else {
              window.okNotify(t('js_error'), 'error');
            }
          }, function () { window.okNotify(t('js_network_error'), 'error'); });
        },

        confirmDelete: function (headingId) {
          if (!confirm(t('text_confirm_delete_heading'))) return;
          this._performHeadingDelete(headingId, false);
        },

        _performHeadingDelete: function (headingId, force) {
          var self = this;
          window.okPost(urls.h_del, { heading_id: headingId, force: force ? 1 : 0 }, function (data) {
            if (data && data.success) {
              window.okNotify(t('text_heading_deleted'), 'success');
              self.load();
              return;
            }
            if (data && data.in_use) {
              var msg = (t('text_heading_in_use') || 'Heading used in %d block(s). Force delete?')
                        .replace('%d', data.usage_count || 0);
              if (confirm(msg)) self._performHeadingDelete(headingId, true);
              return;
            }
            window.okNotify(t('js_error'), 'error');
          });
        },

        bulkDelete: function () {
          if (!this.selected.length) return;
          if (!confirm(t('text_confirm_delete_headings'))) return;
          this._performHeadingsBulkDelete(false);
        },

        _performHeadingsBulkDelete: function (force) {
          var self = this;
          var body = new URLSearchParams();
          self.selected.forEach(function (id) { body.append('heading_ids[]', id); });
          if (force) body.append('force', '1');

          fetch(urls.h_del_many, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body
          })
            .then(function (r) { return r.json(); })
            .then(function (data) {
              if (data && data.success) {
                window.okNotify(t('text_heading_deleted'), 'success');
                self.selected = [];
                self.load();
                return;
              }
              if (data && data.in_use) {
                var n = Object.keys(data.usages_by_heading || {}).length;
                var msg = (t('text_headings_in_use') || '%d headings are in use. Force delete?')
                          .replace('%d', n);
                if (confirm(msg)) self._performHeadingsBulkDelete(true);
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
