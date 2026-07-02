/**
 * EasyCheckout — Abandoned-checkouts module (sub-state of okecApp)
 * © 2026 oc-kit.com | https://oc-kit.com
 */
(function () {
  'use strict';

  window.OkecAbandonedModule = {
    create: function (context, i18n, t) {
      var urls = context.urls || {};

      return {
        items:   [],
        total:   0,
        loading: false,
        stats:   null,
        statsDays: 30,
        search:  '',
        status:  'pending',
        minTotal: 0,
        maxTotal: 0,
        _searchTimer: null,

        load: function () {
          var self = this;
          self.loading = true;
          var url = urls.abandoned_list
                  + '&days='      + (parseInt(self.statsDays, 10) || 30)
                  + '&search='    + encodeURIComponent(self.search || '')
                  + '&status='    + encodeURIComponent(self.status || 'pending')
                  + '&min_total=' + (parseFloat(self.minTotal) || 0)
                  + '&max_total=' + (parseFloat(self.maxTotal) || 0);
          fetch(url, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
              self.loading = false;
              if (data && data.success) {
                self.items = data.items || [];
                self.total = data.total || 0;
                self.stats = data.stats || null;
              }
              if (window.Alpine && window.lucide) {
                Alpine.nextTick(function () { window.lucide.createIcons(); });
              } else if (window.lucide) {
                window.lucide.createIcons();
              }
            })
            .catch(function () { self.loading = false; });
        },

        /** Debounced search input handler. */
        onSearchInput: function () {
          var self = this;
          clearTimeout(self._searchTimer);
          self._searchTimer = setTimeout(function () { self.load(); }, 300);
        },

        formatMoney: function (n) {
          if (typeof n !== 'number') n = parseFloat(n) || 0;
          return n.toFixed(2);
        },

        // Дефолтний date-range для CSV: останні 30 днів
        exportFrom: (function () {
          var d = new Date(); d.setDate(d.getDate() - 30);
          return d.toISOString().slice(0, 10);
        })(),
        exportTo: new Date().toISOString().slice(0, 10),

        exportUrl: function (from, to) {
          if (!urls.orders_export) return '#';
          return urls.orders_export
                 + '&date_from=' + encodeURIComponent(from || '')
                 + '&date_to='   + encodeURIComponent(to   || '');
        },

        /**
         * Send reminder email for a single row immediately, without waiting for cron.
         * Backend re-uses cron logic, marks notified_at, and returns updated row.
         */
        sendReminder: function (item) {
          if (!item || !item.abandoned_id) return;
          if (!confirm(t('abandoned_send_reminder_confirm'))) return;
          var self = this;
          window.okPost(urls.abandoned_send_reminder, {
            abandoned_id: item.abandoned_id,
          }, function (data) {
            if (data && data.success) {
              if (data.notified_at) item.notified_at = data.notified_at;
              window.okNotify(data.message || t('abandoned_reminder_sent'), 'success');
            } else {
              window.okNotify((data && data.message) || t('js_error'), 'error');
            }
          }, function () {
            window.okNotify(t('js_network_error'), 'error');
          });
        },

        copyRecoveryUrl: function (item) {
          if (!item || !item.recovery_url) return;
          var url = item.recovery_url;
          if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(function () {
              window.okNotify(t('text_copied') || 'Copied', 'success');
            }).catch(function () { fallback(); });
          } else {
            fallback();
          }
          function fallback() {
            var ta = document.createElement('textarea');
            ta.value = url;
            document.body.appendChild(ta);
            ta.select();
            try { document.execCommand('copy'); window.okNotify(t('text_copied') || 'Copied', 'success'); }
            catch (e) { window.prompt('Copy:', url); }
            document.body.removeChild(ta);
          }
        },

        delete: function (id) {
          if (!id) return;
          if (!confirm(t('js_confirm'))) return;
          var self = this;
          window.okPost(urls.abandoned_delete, { abandoned_id: id }, function (data) {
            if (data && data.success) {
              self.items = self.items.filter(function (it) { return it.abandoned_id !== id; });
              self.total = Math.max(0, self.total - 1);
              self.selected = self.selected.filter(function (sid) { return sid !== id; });
              window.okNotify(t('js_saved') || 'Deleted', 'success');
            } else {
              window.okNotify((data && data.message) || t('js_error'), 'error');
            }
          }, function () {
            window.okNotify(t('js_network_error'), 'error');
          });
        },

        // ─── Per-row admin note (sales-team comment) ───────────────
        saveNote: function (item) {
          var self = this;
          window.okPost(urls.abandoned_save_note, {
            abandoned_id: item.abandoned_id,
            note: item.admin_notes || '',
          }, function (data) {
            if (data && data.success) {
              window.okNotify(t('js_saved'), 'success');
            } else {
              window.okNotify(t('js_error'), 'error');
            }
          });
        },

        // ─── Products modal (per-row "show what was in cart") ──────
        productsModal: { open: false, id: 0, items: [], loading: false },

        openProducts: function (id) {
          var self = this;
          self.productsModal = { open: true, id: id, items: [], loading: true };
          fetch(urls.abandoned_products + '&abandoned_id=' + id, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
              self.productsModal.loading = false;
              if (data && data.success) self.productsModal.items = data.products || [];
              if (window.lucide) window.lucide.createIcons();
            })
            .catch(function () { self.productsModal.loading = false; });
        },
        closeProducts: function () {
          this.productsModal = { open: false, id: 0, items: [], loading: false };
        },

        // ─── Bulk selection ────────────────────────────────────────
        selected: [],
        toggleSelect: function (id) {
          var idx = this.selected.indexOf(id);
          if (idx === -1) this.selected.push(id);
          else this.selected.splice(idx, 1);
        },
        isSelected: function (id) { return this.selected.indexOf(id) !== -1; },
        toggleSelectAll: function (event) {
          if (event.target.checked) {
            this.selected = this.items.map(function (it) { return it.abandoned_id; });
          } else {
            this.selected = [];
          }
        },
        allSelected: function () {
          return this.items.length > 0 && this.selected.length === this.items.length;
        },
        deleteSelected: function () {
          if (!this.selected.length) return;
          if (!confirm(t('js_confirm'))) return;
          var self = this;
          var ids = this.selected.slice();
          // OC htmlspecialchars не торкається числових значень — passing as-is
          var body = new URLSearchParams();
          ids.forEach(function (id) { body.append('ids[]', id); });
          fetch(urls.abandoned_delete_many, {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body
          })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (data && data.success) {
              self.items = self.items.filter(function (it) { return ids.indexOf(it.abandoned_id) === -1; });
              self.total = Math.max(0, self.total - (data.count || ids.length));
              self.selected = [];
              window.okNotify(t('js_saved') || 'Deleted', 'success');
            } else {
              window.okNotify((data && data.message) || t('js_error'), 'error');
            }
          })
          .catch(function () { window.okNotify(t('js_network_error'), 'error'); });
        },
      };
    }
  };
}());
