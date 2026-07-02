/**
 * EasyCheckout — Admin JS bootstrap
 * © 2026 oc-kit.com | https://oc-kit.com
 *
 * Все важке (Alpine root, sub-modules) — у `modules/*.js`. Тут — тільки
 * універсальний bootstrap іконок Lucide, що мав би рендеритись після Alpine.
 */
(function () {
  'use strict';

  function bootIcons() {
    if (window.lucide && typeof window.lucide.createIcons === 'function') {
      window.lucide.createIcons();
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootIcons);
  } else {
    bootIcons();
  }

  // Alpine стартує сам після DOMContentLoaded; після першого x-init
  // компоненти самі викликають lucide.createIcons() через $nextTick.
}());
