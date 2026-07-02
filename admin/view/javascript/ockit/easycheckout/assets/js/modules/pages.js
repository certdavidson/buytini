/**
 * EasyCheckout — Page layout builder (sub-state of okecApp)
 * © 2026 oc-kit.com | https://oc-kit.com
 *
 * Ієрархія: step → rows → cells → blocks.
 * Drag-drop: блоки рухаються між cells (всі cells в одній Sortable-групі);
 * рядки — між кроками (rows-група).
 */
(function () {
  'use strict';

  function shortId(prefix) {
    return prefix + '_' + Date.now().toString(36) + Math.random().toString(36).slice(2, 5);
  }

  function tick(fn) {
    if (window.Alpine && typeof window.Alpine.nextTick === 'function') {
      window.Alpine.nextTick(fn);
    } else {
      setTimeout(fn, 0);
    }
  }

  /** Set всіх type-ів, вже використаних в layout (для unique-фільтра в add-block dropdown). */
  function usedTypes(steps) {
    var out = [];
    steps.forEach(function (s) {
      (s.rows || []).forEach(function (r) {
        (r.cells || []).forEach(function (c) {
          (c.blocks || []).forEach(function (b) {
            if (out.indexOf(b.type) === -1) out.push(b.type);
          });
        });
      });
    });
    return out;
  }

  window.OkecPagesModule = {
    create: function (context, i18n, t) {
      var blockTypes = context.block_types || [];
      var urls       = context.urls || {};

      return {
        _loaded: false,

        // ─── State ─────────────────────────────────────────────────────
        layout:   { mode: 'single_step', steps: [] },
        viewport: 'desktop',     // desktop | tablet | mobile (preview)
        loading:  false,
        saving:   false,
        warnings: [],            // layout lint warnings (broken refs, empty cells, etc.)
        warningsCollapsed: false,

        // Add-block dropdown — позиція по cellId
        addBlockOpen: { cellId: null, pos: { top: 0, left: 0, width: 240 } },
        // Add-row dropdown — позиція по stepIdx
        addRowOpen:   { stepIdx: -1, pos: { top: 0, left: 0, width: 200 } },

        // Block settings modal
        settingsModal: { open: false, block: null, cellId: null },

        // ─── Helpers ───────────────────────────────────────────────────
        blockMeta: function (type) {
          return blockTypes.find(function (b) { return b.code === type; }) || null;
        },

        blockLabel: function (type) {
          var m = this.blockMeta(type);
          return m ? m.label : type;
        },

        blockIcon: function (type) {
          var m = this.blockMeta(type);
          return m ? m.icon : 'box';
        },

        availableBlocks: function () {
          var used = usedTypes(this.layout.steps);
          return blockTypes.filter(function (b) {
            return !b.unique || used.indexOf(b.code) === -1;
          });
        },

        /** Структурна кількість колонок = кількість комірок рядка (1..3). */
        rowCols: function (row) {
          if (!row || !row.cells) return 1;
          return Math.max(1, Math.min(3, row.cells.length));
        },

        /** Ширина комірки (1..12) для активного viewport. */
        cellSpan: function (cell) {
          if (!cell) return 12;
          var v = this.viewport;
          if (cell.span && cell.span[v]) return Math.max(1, Math.min(12, parseInt(cell.span[v], 10) || 12));
          return 12;
        },

        /** Встановити ширину комірки на активному viewport (1..12). */
        setCellSpan: function (cell, val) {
          val = Math.max(1, Math.min(12, parseInt(val, 10) || 1));
          if (!cell.span) cell.span = { desktop: 12, tablet: 12, mobile: 12 };
          cell.span[this.viewport] = val;
        },

        /** Width-контрол має сенс лише коли в рядку 2+ колонки. */
        showWidthControls: function (row) {
          return row && row.cells && row.cells.length > 1;
        },

        /**
         * Повертає блоки cell-у в порядку, специфічному для активного viewport.
         * — desktop: cell.blocks (canonical structure)
         * — tablet/mobile: cell.order_<viewport> якщо існує, інакше canonical
         * Нові блоки, додані пізніше, теж потрапляють у вивід (append-ляться в кінець override-у).
         */
        cellBlocks: function (cell) {
          if (!cell || !cell.blocks) return [];
          if (this.viewport === 'desktop') return cell.blocks;

          var orderKey = 'order_' + this.viewport;
          var order = cell[orderKey];
          if (!Array.isArray(order) || !order.length) return cell.blocks;

          var byId = {};
          cell.blocks.forEach(function (b) { byId[b.id] = b; });
          var result = [];
          order.forEach(function (id) {
            if (byId[id]) { result.push(byId[id]); delete byId[id]; }
          });
          // блоки яких нема в override (новостворені) — у кінець
          cell.blocks.forEach(function (b) { if (byId[b.id]) result.push(b); });
          return result;
        },

        // ─── Load / save ───────────────────────────────────────────────
        /** Активна група береться з root-компонента через window.OkecApp() — за `data-x-data` контекстом */
        _activeGroupId: function () {
          // app.js ставить window.OkecAppRef = this в init — використовуємо для cross-module read
          if (window.OkecAppRef && typeof window.OkecAppRef.activeGroupId === 'number') {
            return window.OkecAppRef.activeGroupId;
          }
          return (context.active_group_id || 0);
        },

        activeStoreId: 0,
        copySourceStoreId: 0,
        onStoreChange: function () { this.load(); },

        /**
         * Копіює layout з вибраного source-магазину в поточний (target).
         * Backend не зберігає — повертає JSON, ми завантажуємо в state,
         * користувач переглядає та тисне Save.
         */
        copyFromStore: function () {
          var self = this;
          var src = parseInt(self.copySourceStoreId, 10) || 0;
          var dst = parseInt(self.activeStoreId, 10) || 0;
          if (src === dst) return;
          if (!confirm(t('layout_copy_from_confirm') || 'Replace current layout with copy from selected store?')) return;

          var body = new URLSearchParams();
          body.append('page', 'checkout');
          body.append('source_store_id', src);
          body.append('source_group_id', self._activeGroupId());

          fetch(urls.layout_copy_from, {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body
          })
            .then(function (r) { return r.json(); })
            .then(function (data) {
              if (!data || !data.success || !data.layout) {
                window.okNotify((data && data.message) || t('js_error'), 'error');
                return;
              }
              self.layout = data.layout;
              self._initBlockSettings();
              window.okNotify(data.message || t('layout_copied') || 'Layout copied', 'success');
              if (window.Alpine && window.lucide) {
                Alpine.nextTick(function () { window.lucide.createIcons(); });
              }
            })
            .catch(function () { window.okNotify(t('js_network_error'), 'error'); });
        },

        load: function () {
          var self = this;
          self.loading = true;
          var gid = self._activeGroupId();
          var sid = parseInt(self.activeStoreId, 10) || 0;
          fetch(urls.layout_get + '&page=checkout&group_id=' + gid + '&store_id=' + sid, { credentials: 'same-origin' })
            .then(function (r) {
              if (!r.ok) throw new Error('HTTP ' + r.status);
              return r.text();
            })
            .then(function (text) {
              self.loading = false;
              var data;
              try { data = JSON.parse(text); }
              catch (e) {
                console.error('[okec] layout_get returned non-JSON:', text.slice(0, 200));
                window.okNotify(t('js_error'), 'error');
                return;
              }
              if (data && data.success && data.layout) {
                self.layout = data.layout;
                self.warnings = Array.isArray(data.warnings) ? data.warnings : [];
              }
              tick(function () {
                try {
                  if (window.lucide) window.lucide.createIcons();
                  self.reinitSortables();
                } catch (e) {
                  console.error('[okec] post-load init failed:', e);
                }
              });
            })
            .catch(function (err) {
              self.loading = false;
              console.error('[okec] layout_get fetch failed:', err);
              window.okNotify(t('js_network_error'), 'error');
            });
        },

        allCollapsed: false,
        toggleCollapseAll: function () {
          this.allCollapsed = !this.allCollapsed;
          tick(function () { if (window.lucide) window.lucide.createIcons(); });
        },

        previewOpen: false,
        previewUrl:  '',
        previewDevice: 'desktop',   // desktop | tablet | mobile

        /** Ширина iframe-вьюпорта під обраний пристрій. desktop = вся ширина. */
        previewFrameStyle: function () {
          if (this.previewDevice === 'tablet') return 'width:768px;max-width:100%;margin:0 auto;';
          if (this.previewDevice === 'mobile') return 'width:390px;max-width:100%;margin:0 auto;';
          return 'width:100%;';
        },
        previewDims: function () {
          if (this.previewDevice === 'tablet') return '768 px';
          if (this.previewDevice === 'mobile') return '390 px';
          return '100%';
        },

        /** Export URL з activeGroupId та activeStoreId — поточний state. */
        exportUrl: function () {
          var gid = this._activeGroupId();
          var sid = parseInt(this.activeStoreId, 10) || 0;
          return urls.layout_export + '&page=checkout&group_id=' + gid + '&store_id=' + sid;
        },

        openImport: function () {
          var self = this;
          var input = document.createElement('input');
          input.type = 'file';
          input.accept = 'application/json,.json';
          input.onchange = function () {
            if (!input.files || !input.files[0]) return;
            if (!confirm(t('layout_btn_import_confirm') || 'Replace current layout?')) return;
            var fd = new FormData();
            fd.append('file', input.files[0]);
            fd.append('page', 'checkout');
            fd.append('group_id', self._activeGroupId());
            fd.append('store_id', parseInt(self.activeStoreId, 10) || 0);
            fetch(urls.layout_import, { method: 'POST', credentials: 'same-origin', body: fd })
              .then(function (r) { return r.json(); })
              .then(function (data) {
                if (data && data.success) {
                  if (data.layout) self.layout = data.layout;
                  window.okNotify(data.message || t('layout_saved'), 'success');
                  tick(function () {
                    if (window.lucide) window.lucide.createIcons();
                    self.reinitSortables();
                  });
                } else {
                  window.okNotify((data && data.message) || t('js_error'), 'error');
                }
              })
              .catch(function () { window.okNotify(t('js_network_error'), 'error'); });
          };
          input.click();
        },

        openPreview: function () {
          var self = this;
          // Передаємо поточний layout-state (можливо незбережений) — preview покаже його
          var snapshot = JSON.parse(JSON.stringify(self.layout));
          window.okPost(urls.layout_preview, {
            layout: JSON.stringify(snapshot)
          },
          function (data) {
            if (data && data.success && data.url) {
              self.previewUrl  = data.url;
              self.previewOpen = true;
            } else {
              window.okNotify(t('js_error') || 'Failed to open preview', 'error');
            }
          },
          function () { window.okNotify(t('js_network_error') || 'Network error', 'error'); });
        },
        closePreview: function () { this.previewOpen = false; this.previewUrl = ''; },

        resetToDefaults: function () {
          if (!confirm(t('layout_confirm_reset'))) return;
          var self = this;
          self.loading = true;
          fetch(urls.layout_defaults, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
              self.loading = false;
              if (data && data.success && data.layout) {
                self.layout = data.layout;
                window.okNotify(t('layout_reset_done'), 'success');
                tick(function () {
                  if (window.lucide) window.lucide.createIcons();
                  self.reinitSortables();
                });
              } else {
                window.okNotify(t('js_error'), 'error');
              }
            })
            .catch(function () {
              self.loading = false;
              window.okNotify(t('js_network_error'), 'error');
            });
        },

        save: function () {
          var self = this;
          self.saving = true;
          var clean = JSON.parse(JSON.stringify(self.layout));

          window.okPost(urls.layout_save, {
            page:     'checkout',
            group_id: self._activeGroupId(),
            store_id: parseInt(self.activeStoreId, 10) || 0,
            layout:   JSON.stringify(clean)
          },
          function (data) {
            self.saving = false;
            if (data.success) {
              window.okNotify(data.message || t('layout_saved'), 'success');
              if (data.layout) self.layout = data.layout;
              tick(function () {
                if (window.lucide) window.lucide.createIcons();
                self.reinitSortables();
              });
            } else {
              window.okNotify(data.message || t('js_error'), 'error');
            }
          },
          function () {
            self.saving = false;
            window.okNotify(t('js_network_error'), 'error');
          });
        },

        /** Викликати після будь-якої DOM-мутації: реініт іконок Lucide + Sortable.
         *  createIcons — у rAF після Alpine.nextTick: вкладені <template x-for>
         *  (step→row→cell→block) можуть домальовуватись наступним кадром, тож
         *  одного nextTick недостатньо для щойно доданого блока. */
        _postRender: function () {
          var self = this;
          tick(function () {
            self.reinitSortables();
            requestAnimationFrame(function () {
              if (window.lucide) window.lucide.createIcons();
            });
          });
        },

        /** Лайт-варіант: тільки lucide-іконки, без reinit Sortable. */
        _renderIcons: function () {
          tick(function () {
            if (window.lucide) window.lucide.createIcons();
          });
        },

        // ─── Steps ─────────────────────────────────────────────────────
        addStep: function () {
          this.layout.steps.push({
            id:    shortId('step'),
            title: {},
            rows:  [this._emptyRow(1)]
          });
          this._postRender();
        },

        removeStep: function (idx) {
          if (this.layout.steps.length <= 1) return;
          if (!confirm(t('layout_btn_remove_step') + '?')) return;
          this.layout.steps.splice(idx, 1);
          this._postRender();
        },

        // ─── Rows ──────────────────────────────────────────────────────
        _emptyRow: function (columns) {
          var span = Math.max(1, Math.round(12 / columns));
          var cells = [];
          for (var i = 0; i < columns; i++) {
            cells.push({
              id:           shortId('cell'),
              blocks:       [],
              span:         { desktop: span, tablet: span, mobile: 12 },
              order_tablet: null,
              order_mobile: null
            });
          }
          return {
            id:      shortId('row'),
            columns: { desktop: columns, tablet: columns },
            cells:   cells
          };
        },

        addRow: function (stepIdx, columns) {
          this.layout.steps[stepIdx].rows.push(this._emptyRow(columns));
          this.addRowOpen.stepIdx = -1;
          this._postRender();
        },

        removeRow: function (stepIdx, rowIdx) {
          var row = this.layout.steps[stepIdx].rows[rowIdx];
          var hasBlocks = (row.cells || []).some(function (c) { return (c.blocks || []).length > 0; });
          if (hasBlocks && !confirm(t('layout_btn_remove_row') + '?')) return;
          this.layout.steps[stepIdx].rows.splice(rowIdx, 1);
          this._postRender();
        },

        /**
         * Структурна зміна кількості колонок (= кількість комірок). Додає/мерджить
         * cells і скидає ширини (span) до рівного поділу для всіх breakpoint-ів.
         * Точну ширину далі регулюють per-cell слайдери (cellSpan).
         */
        setRowColumns: function (row, n) {
          n = Math.max(1, Math.min(3, parseInt(n, 10) || 1));
          if (!row.cells) row.cells = [];

          if (n > row.cells.length) {
            while (row.cells.length < n) {
              row.cells.push({
                id:           shortId('cell'),
                blocks:       [],
                span:         { desktop: 12, tablet: 12, mobile: 12 },
                order_tablet: null,
                order_mobile: null
              });
            }
          } else if (n < row.cells.length) {
            var tail = [];
            for (var i = n; i < row.cells.length; i++) {
              tail = tail.concat(row.cells[i].blocks || []);
            }
            row.cells.length = n;
            row.cells[n - 1].blocks = (row.cells[n - 1].blocks || []).concat(tail);
          }

          row.columns = { desktop: n, tablet: n };

          // Скидаємо ширини до рівного поділу 12/N (desktop+tablet), mobile — стак.
          var span = Math.max(1, Math.round(12 / n));
          row.cells.forEach(function (c) {
            c.span = { desktop: span, tablet: span, mobile: 12 };
          });

          this._postRender();
        },

        // ─── Blocks ────────────────────────────────────────────────────
        addBlock: function (cellId, type) {
          var meta = this.blockMeta(type);
          if (!meta) return;
          if (meta.unique && usedTypes(this.layout.steps).indexOf(type) !== -1) return;
          var allIds = [];
          this.layout.steps.forEach(function (s) {
            (s.rows || []).forEach(function (r) {
              (r.cells || []).forEach(function (c) {
                (c.blocks || []).forEach(function (b) { allIds.push(b.id); });
              });
            });
          });
          var id = meta.unique ? type : (function () {
            var i = 1, candidate;
            do { candidate = type + '__' + i++; } while (allIds.indexOf(candidate) !== -1);
            return candidate;
          })();

          var cell = this._findCell(cellId);
          if (cell) {
            cell.blocks.push({ id: id, type: type });
            // Якщо override активний — додаємо ID у його кінець
            if (Array.isArray(cell.order_tablet)) cell.order_tablet.push(id);
            if (Array.isArray(cell.order_mobile)) cell.order_mobile.push(id);
          }
          this.addBlockOpen.cellId = null;
          this._postRender();
        },

        removeBlock: function (cellId, blockIdx) {
          var cell = this._findCell(cellId);
          if (!cell) return;
          var removed = cell.blocks.splice(blockIdx, 1)[0];
          if (removed) {
            ['order_tablet', 'order_mobile'].forEach(function (k) {
              if (Array.isArray(cell[k])) {
                cell[k] = cell[k].filter(function (id) { return id !== removed.id; });
              }
            });
          }
          this._postRender();
        },

        /** При drag в межах cell на tablet/mobile — обираємо за ID блока,
         *  не за numeric index, бо порядок виводу != cell.blocks index. */
        removeBlockById: function (cellId, blockId) {
          var cell = this._findCell(cellId);
          if (!cell) return;
          var idx = cell.blocks.findIndex(function (b) { return b.id === blockId; });
          if (idx >= 0) this.removeBlock(cellId, idx);
        },

        /** Дублює блок зі всіма settings (deep copy). Унікальні блоки (cart,
         *  customer, summary тощо) skip — meta.unique флаг. Новий ID — type__N. */
        cloneBlockById: function (cellId, blockId) {
          var cell = this._findCell(cellId);
          if (!cell) return;
          var idx = cell.blocks.findIndex(function (b) { return b.id === blockId; });
          if (idx === -1) return;
          var src = cell.blocks[idx];
          var meta = this.blockMeta(src.type);
          if (!meta) return;
          if (meta.unique) {
            window.okNotify(t('layout_block_unique_no_clone') || 'This block type is unique', 'warning');
            return;
          }

          // Знаходимо вільний ID
          var allIds = [];
          this.layout.steps.forEach(function (s) {
            (s.rows || []).forEach(function (r) {
              (r.cells || []).forEach(function (c) {
                (c.blocks || []).forEach(function (b) { allIds.push(b.id); });
              });
            });
          });
          var i = 1, candidate;
          do { candidate = src.type + '__' + i++; } while (allIds.indexOf(candidate) !== -1);

          // Deep clone settings
          var clone = {
            id:       candidate,
            type:     src.type,
            settings: JSON.parse(JSON.stringify(src.settings || {})),
          };
          // Вставляємо одразу після оригіналу
          cell.blocks.splice(idx + 1, 0, clone);
          this._postRender();
          window.okNotify(t('layout_block_cloned') || 'Block cloned', 'success');
        },

        _findCell: function (cellId) {
          for (var s = 0; s < this.layout.steps.length; s++) {
            var rows = this.layout.steps[s].rows || [];
            for (var r = 0; r < rows.length; r++) {
              var cells = rows[r].cells || [];
              for (var c = 0; c < cells.length; c++) {
                if (cells[c].id === cellId) return cells[c];
              }
            }
          }
          return null;
        },

        // ─── Block settings modal ──────────────────────────────────────
        openBlockSettings: function (block, cellId) {
          if (!block) return;
          this._initBlockSettings(block);
          this.settingsModal.open    = true;
          this.settingsModal.block   = block;
          this.settingsModal.cellId  = cellId;
          // Скидаємо стан add-field dropdown — щоб не вилазив одразу при відкритті
          // нової модалки після того як юзер не закрив його у попередній.
          this.addFieldOpen = false;
          // Освіжаємо список полів (могли додати нові в "Поля" без reload)
          this._refreshFieldsList();
          this._postRender();
        },

        /**
         * Перезавантажує context.fields_list з сервера (in-place mutation,
         * щоб посилання, що вже в closure-ах sub-modules, лишались валідними).
         */
        _refreshFieldsList: function () {
          if (!urls.fields_list) return;
          var ctx = context;
          fetch(urls.fields_list, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
              if (!data || !data.success || !Array.isArray(data.fields)) return;
              // Mutate in-place: clear + push (зберігає посилання масиву)
              ctx.fields_list.length = 0;
              data.fields.forEach(function (f) { ctx.fields_list.push(f); });
            })
            .catch(function () { /* silent — наявний кеш не перезаписується */ });
        },

        /**
         * Гарантує дефолти для всіх ключів settings конкретного типу.
         * Виклик ідемпотентний — існуючі значення не перезаписуються.
         */
        _initBlockSettings: function (block) {
          if (!block.settings || typeof block.settings !== 'object' || Array.isArray(block.settings)) {
            block.settings = {};
          }
          var s = block.settings;

          // Base visibility (всі блоки)
          ['hide_for_guests', 'hide_for_logged_in',
           'hide_on_desktop', 'hide_on_tablet', 'hide_on_mobile'].forEach(function (k) {
            if (typeof s[k] === 'undefined') s[k] = false;
          });

          var langs = context.languages || [];
          var ensureI18n = function (key) {
            if (!s[key] || typeof s[key] !== 'object') s[key] = {};
            langs.forEach(function (lang) {
              if (typeof s[key][lang.language_id] === 'undefined') {
                s[key][lang.language_id] = '';
              }
            });
          };
          var ensureBool = function (key, def) {
            if (typeof s[key] === 'undefined') s[key] = !!def;
          };
          var ensureStr = function (key, def) {
            if (typeof s[key] === 'undefined') s[key] = def;
          };

          switch (block.type) {
            case 'comment':
            case 'help':
            case 'custom_html':
              ensureI18n('text');
              break;
            case 'agreement':
              ensureI18n('text');
              ensureBool('required', true);
              break;
            case 'customer':
              ensureStr('registration_mode', 'optional');   // optional|required|disabled
              ensureBool('show_login_link', true);
              break;
            case 'cart':
              ensureBool('show_image',          true);
              ensureBool('show_model',          false);
              ensureBool('show_quantity_controls', true);
              ensureBool('show_remove_btn',     true);
              ensureBool('show_subtotal',       true);
              break;
            case 'summary':
              ensureBool('show_subtotal',       true);
              ensureBool('show_taxes',          true);
              ensureBool('show_coupon_input',   true);
              ensureBool('show_voucher_input',  false);
              ensureBool('show_reward_input',   false);
              break;
            case 'shipping':
            case 'payment':
              ensureStr('display_mode', 'radio');           // radio|select
              ensureBool('auto_select_first', true);
              ensureBool('show_description', true);
              if (!Array.isArray(s.fields)) s.fields = [];
              break;
            case 'buttons':
              ensureI18n('submit_text');
              ensureI18n('back_text');
              ensureBool('show_agreement_inline', false);
              ensureBool('sticky_on_mobile',      false);
              break;
            case 'payment_address':
              ensureBool('show_company', true);
              ensureBool('same_as_shipping_toggle', true); // default ON: customer-facing checkbox
              if (!Array.isArray(s.fields)) s.fields = [];
              break;
            case 'shipping_address':
              ensureBool('show_company', true);
              if (!Array.isArray(s.fields)) s.fields = [];
              break;
            // payment_form — без додаткових налаштувань (керує модулем оплати)
          }
          // Customer теж має fields
          if (block.type === 'customer' && !Array.isArray(s.fields)) {
            s.fields = [];
          }
          // Backfill + normalize `condition` (legacy single → {match,rules[]})
          var self = this;
          s.condition = self.normalizeCondition(s.condition || null);
          if (Array.isArray(s.fields)) {
            s.fields.forEach(function (f) {
              f.condition = self.normalizeCondition(f.condition || null);
            });
          }
        },

        /**
         * Native-поля логічно прив'язані до типу блоку (password в Доставку — нелогічно).
         * Custom-поля (наші, з реєстру) — без обмежень: користувач сам обирає де їх використати.
         */
        availableFieldsForBlock: function (block) {
          if (!block) return [];
          var allFields = (context.fields_list || []);

          // Жорсткий маппінг native belongs_to → тип блоку
          var nativeAllowed;
          if (block.type === 'customer') {
            nativeAllowed = ['customer'];
          } else if (block.type === 'payment_address' || block.type === 'shipping_address') {
            nativeAllowed = ['address'];
          } else if (block.type === 'shipping' || block.type === 'payment') {
            // shipping/payment приймають address (для address_1, НП-кодів) + order.comment
            nativeAllowed = ['address', 'order'];
          } else {
            nativeAllowed = [];
          }

          var usedIds = (block.settings && Array.isArray(block.settings.fields))
            ? block.settings.fields.map(function (f) { return f.field_id; })
            : [];

          return allFields.filter(function (f) {
            if (usedIds.indexOf(f.field_id) !== -1) return false;
            if (f.native) {
              return nativeAllowed.indexOf(f.belongs_to) !== -1;
            }
            return true;   // custom — доступні скрізь
          });
        },

        /** Чи підтримує блок секцію "Поля". */
        blockSupportsFields: function (block) {
          if (!block) return false;
          return ['customer', 'payment_address', 'shipping_address',
                  'shipping', 'payment'].indexOf(block.type) !== -1;
        },

        fieldInfoById: function (fieldId) {
          return (context.fields_list || []).find(function (f) { return f.field_id === fieldId; }) || null;
        },

        // CRUD для block.settings.fields
        addBlockField: function (block, fieldId) {
          if (!block.settings.fields) block.settings.fields = [];
          if (block.settings.fields.some(function (f) { return f.field_id === fieldId; })) return;
          block.settings.fields.push({
            field_id:         fieldId,
            visibility:       'always',     // always | guests | logged_in
            width:            'full',       // full | half | third | two_thirds
            required:         false,
            reload_on_change: false,
            condition:        null,
          });
          this._renderIcons();
        },

        /** Відкриття/закриття condition-row для поля. */
        toggleFieldCondition: function (item) {
          item.condition = item.condition ? null : this.newCondition();
        },

        /** Порожня умова — match-режим + один порожній rule. */
        newCondition: function () {
          return { match: 'all', rules: [{ source_code: '', operator: '==', value: '' }] };
        },

        /** Нормалізує умову: legacy {source_code,operator,value} → {match,rules[]}. */
        normalizeCondition: function (c) {
          if (!c) return null;
          if (Array.isArray(c.rules)) {
            if (!c.match) c.match = 'all';
            if (c.rules.length === 0) return null;
            return c;
          }
          if (typeof c.source_code !== 'undefined') {
            return { match: 'all', rules: [{
              source_code: c.source_code || '',
              operator:    c.operator || '==',
              value:       c.value || ''
            }] };
          }
          return null;
        },

        addConditionRule: function (cond) {
          if (!cond || !Array.isArray(cond.rules)) return;
          cond.rules.push({ source_code: '', operator: '==', value: '' });
        },

        removeConditionRule: function (item, cond, idx) {
          if (!cond || !Array.isArray(cond.rules)) return;
          cond.rules.splice(idx, 1);
          if (cond.rules.length === 0) {
            // прибрали останній rule — вимикаємо умову зовсім
            if (item && item.settings) { item.settings.condition = null; }
            else if (item) { item.condition = null; }
          }
        },

        /**
         * Список доступних source-codes для block-condition. Включає:
         *   - native field codes (з NativeFieldsRegistry — у context.fields_list з native:true)
         *   - custom field codes
         *   - pseudo-codes для UI-checkbox: register, same_as_shipping
         */
        fieldsAvailableForBlockCondition: function () {
          var out = [];
          (context.fields_list || []).forEach(function (f) {
            out.push({
              code:  f.code,
              label: (f.native ? '⚙ ' : '') + (f.name || f.code),
            });
          });
          out.push({ code: 'register',         label: '⚙ register (checkbox)' });
          out.push({ code: 'same_as_shipping', label: '⚙ same_as_shipping (checkbox)' });
          out.push({ code: 'shipping_method',  label: '⚙ shipping_method (selected)' });
          out.push({ code: 'payment_method',   label: '⚙ payment_method (selected)' });
          return out;
        },

        /** Те саме для всього блоку (block.settings.condition). */
        toggleBlockCondition: function () {
          var bs = this.settingsModal.block && this.settingsModal.block.settings;
          if (!bs) return;
          bs.condition = bs.condition ? null : this.newCondition();
        },

        /** Список field-кодів (з тим самим блоком + native registry), які можна
         *  використовувати як source умови. Виключає сам item. */
        fieldsAvailableAsConditionSource: function (block, currentFieldId) {
          if (!block || !Array.isArray(block.settings.fields)) return [];
          var out = [];
          block.settings.fields.forEach(function (f) {
            if (f.field_id === currentFieldId) return;
            var info = (context.fields_list || []).find(function (x) { return x.field_id === f.field_id; });
            if (!info) return;
            out.push({ code: info.code, label: info.name || info.code });
          });
          // Додаємо власні токени для глобальних UI-полів (register checkbox, same_as_shipping)
          out.push({ code: 'register',         label: 'register (checkbox)' });
          out.push({ code: 'same_as_shipping', label: 'same_as_shipping (checkbox)' });
          return out;
        },

        removeBlockField: function (block, fieldId) {
          if (!block.settings.fields) return;
          block.settings.fields = block.settings.fields.filter(function (f) {
            return f.field_id !== fieldId;
          });
          this._renderIcons();
        },

        moveBlockField: function (block, fromIdx, toIdx) {
          if (!block.settings.fields) return;
          if (toIdx < 0 || toIdx >= block.settings.fields.length) return;
          var arr = block.settings.fields;
          var item = arr.splice(fromIdx, 1)[0];
          arr.splice(toIdx, 0, item);
          this._renderIcons();
        },

        // Add-field dropdown state (per modal). Відкривається ВГОРУ —
        // позиція задається через `bottom` (від низу viewport), щоб довгий список
        // не зникав знизу екрана. CSS Грид-картки в кінці списку — найновіші.
        addFieldOpen: false,
        addFieldPos:  { bottom: 0, left: 0, width: 240 },
        toggleAddField: function (btnEl) {
          if (this.addFieldOpen) { this.addFieldOpen = false; return; }
          var r = btnEl.getBoundingClientRect();
          this.addFieldPos = {
            bottom: window.innerHeight - r.top + 6,   // 6 px gap
            left:   r.left,
            width:  Math.max(260, r.width)
          };
          this.addFieldOpen = true;
          this._renderIcons();
        },
        closeAddField: function () { this.addFieldOpen = false; },

        closeBlockSettings: function () {
          this.settingsModal.open  = false;
          this.settingsModal.block = null;
          this.addFieldOpen        = false;
        },

        /** Чи у блоку є text-настройка (для типів comment/help/agreement/custom_html). */
        blockHasText: function (block) {
          return block && ['comment', 'help', 'agreement', 'custom_html'].indexOf(block.type) !== -1;
        },

        // ─── Add-block / Add-row dropdowns ─────────────────────────────

        /**
         * Quick-add: знаходить останню комірку останнього рядка кроку.
         * Якщо рядків нема — створює перший 1-кол рядок.
         * Повертає cellId для подальшого виклику toggleAddBlock.
         */
        _ensureTargetCell: function (stepIdx) {
          var step = this.layout.steps[stepIdx];
          if (!step.rows || !step.rows.length) {
            step.rows.push(this._emptyRow(1));
          }
          var lastRow = step.rows[step.rows.length - 1];
          if (!lastRow.cells || !lastRow.cells.length) {
            lastRow.cells.push({
              id:           shortId('cell'),
              blocks:       [],
              order_tablet: null,
              order_mobile: null
            });
          }
          return lastRow.cells[lastRow.cells.length - 1].id;
        },

        /** Кнопка "+ Додати блок" внизу кроку — відкриває dropdown для last-cell. */
        toggleQuickAddBlock: function (stepIdx, btnEl) {
          var cellId = this._ensureTargetCell(stepIdx);
          this.toggleAddBlock(cellId, btnEl);
        },

        toggleAddBlock: function (cellId, btnEl) {
          if (this.addBlockOpen.cellId === cellId) {
            this.addBlockOpen.cellId = null;
            return;
          }
          var r = btnEl.getBoundingClientRect();
          this.addBlockOpen = {
            cellId: cellId,
            pos: { top: r.bottom + 4, left: r.left, width: Math.max(240, r.width) }
          };
        },

        closeAddBlock: function () { this.addBlockOpen.cellId = null; },

        toggleAddRow: function (stepIdx, btnEl) {
          if (this.addRowOpen.stepIdx === stepIdx) {
            this.addRowOpen.stepIdx = -1;
            return;
          }
          var r = btnEl.getBoundingClientRect();
          this.addRowOpen = {
            stepIdx: stepIdx,
            pos: { top: r.bottom + 4, left: r.left, width: Math.max(200, r.width) }
          };
        },

        closeAddRow: function () { this.addRowOpen.stepIdx = -1; },

        // ─── Sortable.js ───────────────────────────────────────────────
        reinitSortables: function () {
          if (!window.Sortable) return;
          var self = this;

          // Знищуємо існуючі instances
          document.querySelectorAll('.okec-cell__blocks, .okec-step__rows, .okec-fieldset')
            .forEach(function (el) {
              if (el._sortable) {
                try { el._sortable.destroy(); } catch (e) {}
                el._sortable = null;
              }
            });

          // Sortable per cell — для блоків (одна група, drag між cells)
          document.querySelectorAll('.okec-cell__blocks').forEach(function (el) {
            el._sortable = new window.Sortable(el, {
              group:       'okec-blocks',
              animation:   150,
              handle:      '.okec-block-card__drag',
              ghostClass:  'okec-block-card--ghost',
              chosenClass: 'okec-block-card--chosen',
              dragClass:   'okec-block-card--drag',
              onEnd:       function () { self.syncFromDom(); }
            });
          });

          // Sortable per step — для рядків
          document.querySelectorAll('.okec-step__rows').forEach(function (el) {
            el._sortable = new window.Sortable(el, {
              group:       'okec-rows',
              animation:   150,
              handle:      '.okec-row__drag',
              ghostClass:  'okec-row--ghost',
              chosenClass: 'okec-row--chosen',
              dragClass:   'okec-row--drag',
              onEnd:       function () { self.syncFromDom(); }
            });
          });

          // Sortable для прикріплених полів у block settings modal
          document.querySelectorAll('.okec-fieldset').forEach(function (el) {
            el._sortable = new window.Sortable(el, {
              animation:   150,
              handle:      '.okec-fieldset__handle',
              ghostClass:  'okec-fieldset__row--ghost',
              chosenClass: 'okec-fieldset__row--chosen',
              onEnd:       function () { self.syncFieldsetFromDom(); }
            });
          });
        },

        /** Синхронізація block.settings.fields[] після drag в модалці. */
        syncFieldsetFromDom: function () {
          var block = this.settingsModal.block;
          if (!block || !block.settings || !Array.isArray(block.settings.fields)) return;

          var byId = {};
          block.settings.fields.forEach(function (f) { byId[f.field_id] = f; });

          var newFields = [];
          document.querySelectorAll('.okec-fieldset .okec-fieldset__item').forEach(function (rowEl) {
            var id = parseInt(rowEl.getAttribute('data-field-id'), 10);
            if (!isNaN(id) && byId[id]) {
              newFields.push(byId[id]);
              delete byId[id];
            }
          });
          // Hедодані (теоретично) — у кінець
          Object.keys(byId).forEach(function (k) { newFields.push(byId[k]); });
          block.settings.fields = newFields;
        },

        /**
         * Після Sortable drag-end DOM змінено, але Alpine state — ні.
         * — На desktop: оновлюємо cell.blocks (canonical порядок).
         * — На tablet/mobile: оновлюємо cell.order_<viewport>, cell.blocks НЕ чіпаємо.
         */
        syncFromDom: function () {
          var self = this;
          var oldByBlockId = {};
          var oldByCellId  = {};
          var oldByRowId   = {};
          this.layout.steps.forEach(function (s) {
            (s.rows || []).forEach(function (r) {
              oldByRowId[r.id] = r;
              (r.cells || []).forEach(function (c) {
                oldByCellId[c.id] = c;
                (c.blocks || []).forEach(function (b) { oldByBlockId[b.id] = b; });
              });
            });
          });

          var orderKey = self.viewport === 'desktop' ? null : 'order_' + self.viewport;
          var newSteps = [];

          document.querySelectorAll('.okec-step').forEach(function (stepEl) {
            var sIdx = parseInt(stepEl.getAttribute('data-step-idx'), 10);
            if (isNaN(sIdx)) return;
            var oldStep = self.layout.steps[sIdx];

            var newRows = [];
            stepEl.querySelectorAll('.okec-row').forEach(function (rowEl) {
              var rid = rowEl.getAttribute('data-row-id');
              var oldRow = oldByRowId[rid];
              if (!oldRow) return;

              var newCells = [];
              rowEl.querySelectorAll('.okec-cell').forEach(function (cellEl) {
                var cid = cellEl.getAttribute('data-cell-id');
                var oldCell = oldByCellId[cid];
                if (!oldCell) return;

                var domIds = [];
                cellEl.querySelectorAll('.okec-block-card').forEach(function (cardEl) {
                  var bid = cardEl.getAttribute('data-block-id');
                  if (bid) domIds.push(bid);
                });

                if (orderKey === null) {
                  // Desktop drag → перебудовуємо canonical blocks за DOM-порядком
                  var newBlocks = [];
                  domIds.forEach(function (bid) {
                    if (oldByBlockId[bid]) newBlocks.push(oldByBlockId[bid]);
                  });
                  newCells.push({
                    id:           cid,
                    blocks:       newBlocks,
                    order_tablet: oldCell.order_tablet || null,
                    order_mobile: oldCell.order_mobile || null
                  });
                } else {
                  // Tablet/Mobile drag → пишемо в order_<viewport>, blocks залишаються
                  var clone = {
                    id:           cid,
                    blocks:       oldCell.blocks,
                    order_tablet: oldCell.order_tablet || null,
                    order_mobile: oldCell.order_mobile || null
                  };
                  clone[orderKey] = domIds;
                  newCells.push(clone);
                }
              });

              newRows.push({
                id:      rid,
                columns: oldRow.columns,
                cells:   newCells
              });
            });

            newSteps[sIdx] = {
              id:    oldStep.id,
              title: oldStep.title,
              rows:  newRows
            };
          });

          this.layout.steps = newSteps;
        },

        /** Скинути override порядку для cell на активному viewport (повернутись до canonical). */
        resetOrderOverride: function (cell) {
          if (this.viewport === 'desktop') return;
          cell['order_' + this.viewport] = null;
          this._postRender();
        },

        /** Чи є кастомний порядок на активному viewport для цієї cell. */
        hasOrderOverride: function (cell) {
          if (this.viewport === 'desktop') return false;
          return Array.isArray(cell['order_' + this.viewport]) && cell['order_' + this.viewport].length > 0;
        }
      };
    }
  };
}());
