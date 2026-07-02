/**
 * EasyCheckout — Root Alpine component (`okecApp`)
 * © 2026 oc-kit.com | https://oc-kit.com
 *
 * Керує:
 *   — поточною секцією (sidebar tab) з підтримкою history.pushState/popstate;
 *   — централізованим i18n хелпером t();
 *   — посиланням на context (languages, field_types, urls);
 *   — sub-state модулями (fields, headings...) — кожен реєструє себе через
 *     window.OkecFieldsModule, тощо.
 */
(function () {
  'use strict';

  /**
   * Початкова секція беремо з URL ?section=… один раз при завантаженні.
   * Потім таби живуть тільки в Alpine state — URL не оновлюється
   * (така сама поведінка, як у oc-kit Products Scraper).
   */
  function getSectionFromQuery(defaultSection) {
    var v = new URLSearchParams(window.location.search).get('section');
    return v === null ? defaultSection : v;
  }

  window.okecApp = function (initialSection) {
    var ctx  = window.OkEasycheckoutContext || { languages: [], field_types: [], urls: {} };
    var i18n = window.OkEasycheckoutI18n || {};

    return {
      // ─── State ────────────────────────────────────────────────────────
      section: getSectionFromQuery(initialSection || 'general'),
      context: ctx,
      // Active editing group — впливає на pageLayoutGet/Save (per-group layout).
      // Інші секції (fields, headings) — глобальні.
      activeGroupId: ctx.active_group_id || 0,

      // Sub-modules (заповнюються зовнішніми реєстраторами)
      fields: window.OkecFieldsModule
        ? window.OkecFieldsModule.create(ctx, i18n, function (k) { return i18n[k] !== undefined ? i18n[k] : k; })
        : null,
      headings: window.OkecHeadingsModule
        ? window.OkecHeadingsModule.create(ctx, i18n, function (k) { return i18n[k] !== undefined ? i18n[k] : k; })
        : null,
      pages: window.OkecPagesModule
        ? window.OkecPagesModule.create(ctx, i18n, function (k) { return i18n[k] !== undefined ? i18n[k] : k; })
        : null,
      groups: window.OkecGroupsModule
        ? window.OkecGroupsModule.create(ctx, i18n, function (k) { return i18n[k] !== undefined ? i18n[k] : k; })
        : null,
      abandoned: window.OkecAbandonedModule
        ? window.OkecAbandonedModule.create(ctx, i18n, function (k) { return i18n[k] !== undefined ? i18n[k] : k; })
        : null,

      // ─── Init ─────────────────────────────────────────────────────────
      init: function () {
        var self = this;
        // Реєструємо self як глобальну точку доступу для sub-модулів
        // (їм потрібен activeGroupId та інше зі спільного state).
        window.OkecAppRef = self;
        // Перший вхід у поточну секцію (lazy-load даних)
        self.onSectionEnter(self.section);
        self.$nextTick(function () {
          if (window.lucide) window.lucide.createIcons();
        });
      },

      // ─── Helpers ──────────────────────────────────────────────────────
      t: function (key) {
        return i18n[key] !== undefined ? i18n[key] : key;
      },

      setSection: function (section) {
        if (section === this.section) return;
        this.section = section;
        this.onSectionEnter(section);
      },

      /** Перемикає активну групу для редагування layout/блоків. */
      setActiveGroup: function (groupId) {
        groupId = parseInt(groupId, 10) || 0;
        if (groupId === this.activeGroupId) return;
        this.activeGroupId = groupId;
        // Reload pages.layout для нової групи
        if (this.pages && typeof this.pages.load === 'function') {
          this.pages._loaded = false;
          this.pages.load();
        }
      },

      /** Поточна активна група (об'єкт з context.groups_list) або null. */
      activeGroup: function () {
        var id = this.activeGroupId;
        var list = (this.context && this.context.groups_list) || [];
        return list.find(function (g) { return g.group_id === id; }) || null;
      },

      onSectionEnter: function (section) {
        var self = this;
        // Lazy-load даних секції при першому вході
        if (section === 'fields' && self.fields && !self.fields._loaded) {
          self.fields.load();
          self.fields._loaded = true;
        }
        if (section === 'headings' && self.headings && !self.headings._loaded) {
          self.headings.load();
          self.headings._loaded = true;
        }
        if (section === 'pages' && self.pages && !self.pages._loaded) {
          self.pages.load();
          self.pages._loaded = true;
        }
        if (section === 'groups' && self.groups && !self.groups._loaded) {
          self.groups.load();
          self.groups._loaded = true;
        }
        if (section === 'abandoned' && self.abandoned && !self.abandoned._loaded) {
          self.abandoned.load();
          self.abandoned._loaded = true;
        }
        // Перерендер іконок після переключення табів
        self.$nextTick(function () {
          if (window.lucide) window.lucide.createIcons();
        });
      }
    };
  };
}());
