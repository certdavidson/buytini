/**
 * EasyCheckout — Settings groups module (sub-state of okecApp)
 * © 2026 oc-kit.com | https://oc-kit.com
 *
 * Управління групами налаштувань (alternative configs).
 * Кожна група — окремий "проект" розкладки checkout.
 */
(function () {
  'use strict';

  function buildEmptyForm() {
    return {
      group_id:   0,
      name:       '',
      slug:       '',
      is_default: false,
      sort_order: 0,
    };
  }

  function tick(fn) {
    if (window.Alpine && typeof window.Alpine.nextTick === 'function') {
      window.Alpine.nextTick(fn);
    } else {
      setTimeout(fn, 0);
    }
  }

  /** Простий slugify: латинські літери + цифри + дефіс. Транслітерація обрізана. */
  function slugify(s) {
    var map = {
      'а':'a','б':'b','в':'v','г':'g','ґ':'g','д':'d','е':'e','є':'ye','ж':'zh','з':'z',
      'и':'y','і':'i','ї':'yi','й':'y','к':'k','л':'l','м':'m','н':'n','о':'o','п':'p',
      'р':'r','с':'s','т':'t','у':'u','ф':'f','х':'h','ц':'ts','ч':'ch','ш':'sh','щ':'sch',
      'ь':'','ю':'yu','я':'ya',"ъ":"","ё":"e","ы":"y","э":"e"
    };
    return (s || '').toString().toLowerCase()
      .split('').map(function (c) { return map[c] !== undefined ? map[c] : c; }).join('')
      .replace(/[^a-z0-9_-]+/g, '-')
      .replace(/^-+|-+$/g, '')
      .slice(0, 64);
  }

  window.OkecGroupsModule = {
    create: function (context, i18n, t) {
      var urls = context.urls || {};

      return {
        _loaded:  false,
        items:    [],
        loading:  false,

        modal:   { open: false, mode: 'create', editing: null },
        form:    buildEmptyForm(),
        errors:  {},
        saving:  false,

        // Clone modal
        cloneModal: { open: false, sourceId: 0 },
        cloneForm:  { name: '', slug: '' },

        load: function () {
          var self = this;
          self.loading = true;
          fetch(urls.g_list, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
              self.loading = false;
              self.items = (data && data.items) || (data && data.groups) || [];
              // Синхронізуємо context.groups_list (in-place) — щоб селектор групи у header оновлювався
              if (Array.isArray(context.groups_list)) {
                context.groups_list.length = 0;
                self.items.forEach(function (g) { context.groups_list.push(g); });
              } else {
                context.groups_list = self.items.slice();
              }
              if (window.lucide) tick(function () {
                window.lucide.createIcons();
                self._initSortable();
              });
            })
            .catch(function () {
              self.loading = false;
              window.okNotify(t('js_network_error'), 'error');
            });
        },

        _sortableInstance: null,
        _initSortable: function () {
          var tbody = document.querySelector('[data-okec-sortable-groups]');
          if (!tbody || !window.Sortable) return;
          if (this._sortableInstance) { try { this._sortableInstance.destroy(); } catch (e) {} }
          var self = this;
          this._sortableInstance = new window.Sortable(tbody, {
            animation: 150,
            handle: '.okec-drag-handle',
            ghostClass: 'okec-row-dragging',
            onEnd: function () {
              // Збираємо нові sort-orders і POST до server
              var order = [];
              tbody.querySelectorAll('tr[data-group-id]').forEach(function (tr, idx) {
                order.push({ group_id: parseInt(tr.dataset.groupId, 10), sort_order: idx });
              });
              self._persistSortOrder(order);
            }
          });
        },

        _persistSortOrder: function (order) {
          var self = this;
          // Повний save кожної group з оновленим sort_order — простий path
          var pending = order.length;
          if (!pending) return;
          order.forEach(function (o) {
            var g = self.items.find(function (x) { return x.group_id === o.group_id; });
            if (!g) { pending--; return; }
            window.okPost(urls.g_save, {
              group_id:   g.group_id,
              name:       g.name,
              slug:       g.slug,
              is_default: g.is_default ? 1 : 0,
              sort_order: o.sort_order,
            }, function () {
              g.sort_order = o.sort_order;
              if (--pending === 0) window.okNotify(t('text_group_saved') || 'Saved', 'success');
            });
          });
        },

        // ─── Create / Edit ────────────────────────────────────────────
        openCreate: function () {
          this.errors = {};
          this.form = buildEmptyForm();
          this.modal = { open: true, mode: 'create', editing: null };
        },

        openEdit: function (group) {
          this.errors = {};
          this.form = {
            group_id:   group.group_id,
            name:       group.name,
            slug:       group.slug,
            is_default: !!group.is_default,
            sort_order: group.sort_order,
          };
          this.modal = { open: true, mode: 'edit', editing: group.group_id };
        },

        closeModal: function () {
          this.modal.open = false;
          this.errors = {};
        },

        autoSlug: function () {
          // Якщо slug не торкався вручну (порожній або відповідає попередньому slugify)
          if (!this.form.slug || this.form.slug === slugify(this.form._lastName || '')) {
            this.form.slug = slugify(this.form.name);
          }
          this.form._lastName = this.form.name;
        },

        /** Inline rename: dblclick на name → input → blur зберігає. */
        inlineRename: function (g, newName) {
          newName = (newName || '').trim();
          if (newName === '' || newName === g.name) return;
          var self = this;
          window.okPost(urls.g_save, {
            group_id:   g.group_id,
            name:       newName,
            slug:       g.slug,
            is_default: g.is_default ? 1 : 0,
            sort_order: g.sort_order || 0,
          }, function (data) {
            if (data && data.success) {
              g.name = newName;
              window.okNotify(t('text_group_saved'), 'success');
            } else {
              window.okNotify((data && data.message) || t('js_error'), 'error');
            }
          });
        },

        save: function () {
          var self = this;
          self.errors = {};
          self.saving = true;

          var payload = {
            group_id:   self.form.group_id || 0,
            name:       self.form.name,
            slug:       self.form.slug,
            is_default: self.form.is_default ? 1 : 0,
            sort_order: self.form.sort_order || 0,
          };
          window.okPost(urls.g_save, payload,
            function (data) {
              self.saving = false;
              if (data.success) {
                window.okNotify(data.message || t('text_group_saved'), 'success');
                self.closeModal();
                self.load();
              } else {
                self.errors = data.errors || {};
                window.okNotify(data.message || t('js_error'), 'error');
              }
            },
            function () {
              self.saving = false;
              window.okNotify(t('js_network_error'), 'error');
            }
          );
        },

        // ─── Delete ───────────────────────────────────────────────────
        confirmDelete: function (group) {
          if (group.is_default) {
            window.okNotify(t('error_group_cannot_delete_default'), 'warning');
            return;
          }
          if (!confirm(t('text_confirm_delete_group').replace('{name}', group.name))) return;
          var self = this;
          window.okPost(urls.g_delete, { group_id: group.group_id },
            function (data) {
              if (data.success) {
                window.okNotify(data.message || t('text_group_deleted'), 'success');
                self.load();
              } else {
                window.okNotify(data.message || t('js_error'), 'error');
              }
            }
          );
        },

        // ─── Clone ────────────────────────────────────────────────────
        openClone: function (sourceGroup) {
          this.cloneForm = {
            name: (sourceGroup.name || '') + ' (copy)',
            slug: slugify((sourceGroup.slug || 'group') + '-copy'),
          };
          this.cloneModal = { open: true, sourceId: sourceGroup.group_id };
        },

        closeClone: function () { this.cloneModal.open = false; },

        cloneAutoSlug: function () {
          this.cloneForm.slug = slugify(this.cloneForm.name);
        },

        doClone: function () {
          var self = this;
          self.saving = true;
          window.okPost(urls.g_clone, {
            source_group_id: self.cloneModal.sourceId,
            name:            self.cloneForm.name,
            slug:            self.cloneForm.slug,
          },
            function (data) {
              self.saving = false;
              if (data.success) {
                window.okNotify(data.message || t('text_group_cloned'), 'success');
                self.closeClone();
                self.load();
              } else {
                window.okNotify(data.message || t('js_error'), 'error');
              }
            },
            function () {
              self.saving = false;
              window.okNotify(t('js_network_error'), 'error');
            }
          );
        },

        formatDate: function (d) {
          if (!d) return '';
          return d.replace('T', ' ').slice(0, 16);
        },
      };
    }
  };
}());
