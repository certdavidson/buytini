/* ==========================================================================
   buytini — UI interactions (vanilla JS, no data rendering)
   Усе керується через data-атрибути + делегування подій.
   Підключати в кінці <body>: <script src="js/main.js" defer></script>
   ========================================================================== */
(function () {
  "use strict";

  /* ---------- Тема (light / dark) ----------
     Раннє застосування теми робиться інлайн-скриптом у <head>,
     щоб уникнути «спалаху». Тут лише перемикання. */
  function currentTheme() {
    return document.documentElement.getAttribute("data-theme") || "light";
  }
  function toggleTheme() {
    var next = currentTheme() === "dark" ? "light" : "dark";
    document.documentElement.setAttribute("data-theme", next);
    try { localStorage.setItem("buytini-theme", next); } catch (e) {}
  }

  /* ---------- Тости ---------- */
  function ensureToastHost() {
    var host = document.getElementById("toasts");
    if (!host) {
      host = document.createElement("div");
      host.id = "toasts";
      host.className = "toasts";
      document.body.appendChild(host);
    }
    return host;
  }
  function toast(type, msg) {
    var host = ensureToastHost();
    var el = document.createElement("div");
    el.className = "toast toast--" + (type || "success");
    el.innerHTML = '<span class="toast__dot"></span><span class="toast__msg"></span>';
    el.querySelector(".toast__msg").textContent = msg;
    host.appendChild(el);
    setTimeout(function () { el.remove(); }, 3200);
  }
  window.buytiniToast = toast;

  /* ---------- Хелпери показу/приховування ---------- */
  function show(el) { if (el) el.hidden = false; }
  function hide(el) { if (el) el.hidden = true; }
  function byId(id) { return document.getElementById(id); }

  // Перенумерувати рядки товарів (викуп)
  function renumberRows(list) {
    var rows = list.querySelectorAll(".bo-row");
    rows.forEach(function (row, i) {
      var badge = row.querySelector(".bo-row__badge");
      var label = row.querySelector("[data-row-label]");
      if (badge) badge.textContent = String(i + 1);
      if (label) label.textContent = "Товар " + (i + 1);
      var rm = row.querySelector(".bo-row__remove");
      if (rm) rm.style.display = rows.length > 1 ? "" : "none";
    });
  }

  // Закрити всі спливаючі шари
  function closeAll(except) {
    document.querySelectorAll("[data-popover]").forEach(function (el) {
      if (el !== except) el.hidden = true;
    });
    document.querySelectorAll(".dropdown.is-open").forEach(function (d) {
      d.classList.remove("is-open");
    });
    var catBtn = document.querySelector(".catalog-btn.is-open");
    if (catBtn) catBtn.classList.remove("is-open");
  }

  /* ---------- Дропдауни (мова, валюта, акаунт) ---------- */
  function toggleDropdown(trigger) {
    var dd = trigger.closest(".dropdown");
    if (!dd) return;
    var willOpen = !dd.classList.contains("is-open");
    closeAll();
    dd.classList.toggle("is-open", willOpen);
    var menu = dd.querySelector("[data-popover]");
    if (menu) menu.hidden = !willOpen;
  }

  /* ---------- Каталог (мега-меню) ---------- */
  function toggleMega() {
    var mega = byId("mega");
    var overlay = byId("mega-overlay");
    var btn = document.querySelector('[data-toggle="mega"]');
    var willOpen = mega && mega.hidden;
    closeAll();
    if (willOpen) {
      // позиція під шапкою
      var header = document.querySelector(".header");
      var top = header ? Math.max(0, Math.round(header.getBoundingClientRect().bottom)) : 110;
      if (mega) { mega.style.top = top + "px"; mega.style.maxHeight = "calc(100vh - " + top + "px)"; }
      if (overlay) { overlay.style.top = top + "px"; }
      if (mega) mega.classList.remove("is-l2");
      show(mega); show(overlay);
      if (btn) btn.classList.add("is-open");
    } else {
      hide(mega); hide(overlay);
      if (btn) btn.classList.remove("is-open");
    }
  }
  function selectMegaCat(idx) {
    document.querySelectorAll("[data-mega-cat]").forEach(function (b) {
      b.classList.toggle("is-active", b.getAttribute("data-mega-cat") === idx);
    });
    document.querySelectorAll("[data-mega-panel]").forEach(function (p) {
      p.classList.toggle("is-active", p.getAttribute("data-mega-panel") === idx);
    });
    var mega = byId("mega");
    if (mega) {
      mega.classList.add("is-l2");
      var lbl = document.querySelector('[data-mega-cat="' + idx + '"] .mega__cat-label');
      var title = mega.querySelector(".mega__back-title");
      if (lbl && title) title.textContent = lbl.textContent.trim();
      var scroller = mega;
      if (scroller) scroller.scrollTop = 0;
    }
  }
  function megaBack() {
    var mega = byId("mega");
    if (mega) { mega.classList.remove("is-l2"); mega.scrollTop = 0; }
  }

  /* ---------- Кошик (offcanvas) ---------- */
  function toggleCart() {
    var panel = byId("cart-panel");
    var overlay = byId("cart-overlay");
    var willOpen = panel && panel.hidden;
    closeAll();
    if (willOpen) { show(panel); show(overlay); }
    else { hide(panel); hide(overlay); }
  }

  /* ---------- Пошук (живі результати) ---------- */
  function onSearchInput(input) {
    var results = byId("search-results");
    var overlay = byId("search-overlay");
    var has = input.value.trim().length > 0;
    if (has) { show(results); show(overlay); }
    else { hide(results); hide(overlay); }
    var wrap = input.closest(".search");
    var clear = wrap && wrap.querySelector("[data-search-clear]");
    if (clear) clear.hidden = input.value.length === 0;
  }

  /* ---------- Модалка авторизації ---------- */
  function openAuth() {
    closeAll();
    show(byId("auth-modal"));
  }
  function closeAuth() { hide(byId("auth-modal")); }
  function authTab(method) {
    document.querySelectorAll("[data-auth-tab]").forEach(function (t) {
      t.classList.toggle("is-active", t.getAttribute("data-auth-tab") === method);
    });
    document.querySelectorAll("[data-auth-method]").forEach(function (m) {
      m.classList.toggle("is-active", m.getAttribute("data-auth-method") === method);
    });
  }
  window.buytiniOpenAuth = openAuth;

  /* ---------- Фільтри каталогу ---------- */
  function toggleSort(trigger) {
    var menu = byId("sort-menu");
    var willOpen = menu && menu.hidden;
    closeAll();
    menu.hidden = !willOpen;
  }
  function pickSort(item) {
    document.querySelectorAll("[data-sort-item]").forEach(function (i) { i.classList.remove("is-active"); });
    item.classList.add("is-active");
    var label = document.querySelector("[data-sort-label]");
    if (label) label.textContent = item.textContent.trim();
    hide(byId("sort-menu"));
  }
  function setView(view) {
    document.querySelectorAll("[data-view]").forEach(function (b) {
      b.classList.toggle("is-active", b.getAttribute("data-view") === view);
    });
    var grid = byId("product-grid");
    if (!grid) return;
    grid.classList.toggle("is-list", view === "list");
    grid.querySelectorAll(".product-card").forEach(function (c) {
      c.classList.toggle("product-card--list", view === "list");
    });
  }
  function openFilters() {
    var side = byId("filters");
    var back = byId("filters-backdrop");
    if (side) side.classList.add("is-open");
    show(back);
  }
  function closeFilters() {
    var side = byId("filters");
    if (side) side.classList.remove("is-open");
    hide(byId("filters-backdrop"));
  }
  function clearFilters() {
    document.querySelectorAll(".filters .check.is-checked").forEach(function (c) { c.classList.remove("is-checked"); });
    document.querySelectorAll(".filters .size-btn.is-active").forEach(function (c) { c.classList.remove("is-active"); });
    document.querySelectorAll(".filters .swatch.is-active").forEach(function (c) { c.classList.remove("is-active"); });
    document.querySelectorAll(".filters .toggle.is-on").forEach(function (c) { c.classList.remove("is-on"); });
    document.querySelectorAll("[data-chip]").forEach(function (ch) { ch.remove(); });
  }

  /* ---------- Універсальні таби (data-tab-group / data-tab / data-tab-panel) ---------- */
  function switchTab(group, key) {
    document.querySelectorAll('[data-tab-group="' + group + '"][data-tab]').forEach(function (b) {
      b.classList.toggle("is-active", b.getAttribute("data-tab") === key);
    });
    document.querySelectorAll('[data-tab-group="' + group + '"][data-tab-panel]').forEach(function (p) {
      p.hidden = p.getAttribute("data-tab-panel") !== key;
    });
  }

  /* ---------- Універсальні модалки (data-modal-open="name" → #modal-name) ---------- */
  function openModal(name) {
    var m = byId("modal-" + name);
    if (!m) return;
    closeAll();
    show(m);
  }

  /* ---------- Галерея товару ---------- */
  function galleryThumbs() { return Array.prototype.slice.call(document.querySelectorAll("[data-gallery-thumb]")); }
  function setGallery(idx) {
    var thumbs = galleryThumbs();
    if (!thumbs.length) return;
    idx = (idx + thumbs.length) % thumbs.length;
    var main = byId("gallery-main");
    var img = thumbs[idx].getAttribute("data-img");
    if (main) { main.style.backgroundImage = img; main.setAttribute("data-current", String(idx)); }
    thumbs.forEach(function (t, i) { t.classList.toggle("is-active", i === idx); });
    var counter = document.querySelector("[data-gallery-counter]");
    if (counter) counter.textContent = (idx + 1) + "/" + thumbs.length;
    var lb = byId("modal-lightbox");
    if (lb) { var lbImg = lb.querySelector("[data-lightbox-img]"); if (lbImg) lbImg.style.backgroundImage = img; }
  }
  function galleryStep(dir) {
    var main = byId("gallery-main");
    var cur = main ? parseInt(main.getAttribute("data-current") || "0", 10) : 0;
    setGallery(cur + dir);
  }

  /* ---------- Опції товару (колір / розмір) ---------- */
  function pickOption(btn, attr) {
    if (btn.classList.contains("is-disabled")) return;
    var group = btn.parentElement;
    group.querySelectorAll("[" + attr + "]").forEach(function (b) { b.classList.remove("is-active"); });
    btn.classList.add("is-active");
    var labelSel = attr === "data-color" ? "[data-color-label]" : "[data-size-label]";
    var label = document.querySelector(labelSel);
    if (label) label.textContent = btn.getAttribute(attr === "data-color" ? "data-color-name" : "data-size") || btn.textContent.trim();
  }

  /* ---------- Рейтинг-зірки у формі відгуку ---------- */
  function setStars(n) {
    document.querySelectorAll("[data-star]").forEach(function (s) {
      var v = parseInt(s.getAttribute("data-star"), 10);
      var svg = s.querySelector("svg");
      if (svg) svg.setAttribute("fill", v <= n ? "var(--accent)" : "none");
    });
    var input = byId("review-rating");
    if (input) input.value = String(n);
  }

  /* ---------- Навігація кроків / редіректи (авторизація) ---------- */
  function applyStep(el) {
    var go = el.getAttribute("data-go");
    if (go) { window.location.href = go; return true; }
    var show = el.getAttribute("data-show");
    var hide = el.getAttribute("data-hide");
    if (show || hide) {
      if (hide) { var h = document.querySelector(hide); if (h) h.hidden = true; }
      if (show) { var sh = document.querySelector(show); if (sh) sh.hidden = false; }
      return true;
    }
    return false;
  }

  /* ---------- Делегування кліків ---------- */
  document.addEventListener("click", function (e) {
    var t = e.target;

    // Модалки
    var modalOpen = t.closest("[data-modal-open]");
    if (modalOpen) { e.preventDefault(); openModal(modalOpen.getAttribute("data-modal-open")); return; }
    if (t.closest("[data-modal-close]")) { var md = t.closest(".modal"); hide(md); return; }
    if (t.classList && t.classList.contains("modal")) { hide(t); return; }

    // Галерея
    var gThumb = t.closest("[data-gallery-thumb]");
    if (gThumb) { setGallery(parseInt(gThumb.getAttribute("data-gallery-thumb"), 10)); return; }
    if (t.closest("[data-gallery-prev]")) { galleryStep(-1); return; }
    if (t.closest("[data-gallery-next]")) { galleryStep(1); return; }

    // Опції товару
    var colorBtn = t.closest("[data-color]");
    if (colorBtn) { pickOption(colorBtn, "data-color"); return; }
    var sizeBtn = t.closest("[data-size]");
    if (sizeBtn) { pickOption(sizeBtn, "data-size"); return; }

    // Вибір картки (доставка / оплата на чекауті)
    var selectCard = t.closest("[data-select-group]");
    if (selectCard) {
      var grp = selectCard.getAttribute("data-select-group");
      document.querySelectorAll('[data-select-group="' + grp + '"]').forEach(function (c) { c.classList.remove("is-active"); });
      selectCard.classList.add("is-active");
      return;
    }

    // Кнопкові групи з одиночним вибором (алфавіт, категорії, регіони)
    var groupBtn = t.closest(".alpha__btn, .cat-pills__btn");
    if (groupBtn) {
      var parent = groupBtn.parentElement;
      parent.querySelectorAll(".is-active").forEach(function (b) { b.classList.remove("is-active"); });
      groupBtn.classList.add("is-active");
      return;
    }

    // Зірки відгуку
    var starBtn = t.closest("[data-star]");
    if (starBtn) { setStars(parseInt(starBtn.getAttribute("data-star"), 10)); return; }

    // Картка товару (PDP): купити / обране
    if (t.closest("[data-pdp-add]")) {
      e.preventDefault();
      var colorEl = document.querySelector("[data-color-label]");
      var sizeEl = document.querySelector("[data-size-label]");
      var opts = byId("modal-added") && byId("modal-added").querySelector("[data-added-opts]");
      if (opts) {
        var parts = [];
        if (colorEl) parts.push("Колір: " + colorEl.textContent.trim());
        if (sizeEl) parts.push("Розмір: " + sizeEl.textContent.trim());
        opts.textContent = parts.join(" · ");
      }
      if (byId("modal-added")) openModal("added");
      else toast("success", "Товар додано в кошик");
      return;
    }
    var cartRemove = t.closest("[data-cart-remove]");
    if (cartRemove) { var ci = cartRemove.closest(".cart-item"); if (ci) ci.remove(); toast("info", "Товар видалено з кошика"); return; }
    var pdpWish = t.closest("[data-pdp-wish]");
    if (pdpWish) { e.preventDefault(); pdpWish.classList.toggle("is-active"); toast("info", pdpWish.classList.contains("is-active") ? "Додано в обране" : "Прибрано з обраного"); return; }

    // Промо-смуга — закрити
    var promoClose = t.closest("[data-promo-close]");
    if (promoClose) { var promo = promoClose.closest(".promo"); if (promo) promo.remove(); return; }

    // FAQ-акордеон (single-open)
    var faqBtn = t.closest("[data-faq-toggle]");
    if (faqBtn) {
      var item = faqBtn.closest(".faq__item");
      var wasOpen = item.classList.contains("is-open");
      var scope = faqBtn.closest("[data-faq]") || document;
      scope.querySelectorAll(".faq__item.is-open").forEach(function (i) { i.classList.remove("is-open"); });
      if (!wasOpen) item.classList.add("is-open");
      return;
    }

    // Кроки / редіректи (кнопки без сабміту)
    var stepBtn = t.closest("[data-go],[data-show],[data-hide]");
    if (stepBtn && stepBtn.tagName !== "FORM" && stepBtn.type !== "submit") { if (applyStep(stepBtn)) { e.preventDefault(); return; } }

    // Чекбокс-перемикач (згода тощо)
    var checkToggle = t.closest("[data-check-toggle]");
    if (checkToggle) { checkToggle.classList.toggle("is-checked"); return; }

    // Повторна відправка коду/листа
    if (t.closest("[data-form-toast]")) { toast("success", "Надіслано ще раз"); return; }

    // Кнопки з тост-повідомленням (зберегти профіль, адресу тощо)
    var toastBtn = t.closest("[data-toast]");
    if (toastBtn) { e.preventDefault(); toast(toastBtn.getAttribute("data-toast-type") || "success", toastBtn.getAttribute("data-toast")); return; }

    // Копіювати промокод
    var copyBtn = t.closest("[data-copy]");
    if (copyBtn) {
      var code = copyBtn.getAttribute("data-copy");
      try { if (navigator.clipboard) navigator.clipboard.writeText(code); } catch (err) {}
      copyBtn.classList.add("is-copied");
      toast("success", "Код «" + code + "» скопійовано");
      setTimeout(function () { copyBtn.classList.remove("is-copied"); }, 2000);
      return;
    }

    // Будь-який перемикач поза фільтрами (сповіщення в профілі)
    var anyToggle = t.closest(".toggle");
    if (anyToggle) { anyToggle.classList.toggle("is-on"); return; }

    // Викуп: додати / видалити рядок товару
    if (t.closest("[data-add-row]")) {
      e.preventDefault();
      var list = byId("bo-rows");
      if (list) {
        var rows = list.querySelectorAll(".bo-row");
        var clone = rows[rows.length - 1].cloneNode(true);
        clone.querySelectorAll("input, textarea").forEach(function (f) { f.value = ""; });
        list.appendChild(clone);
        renumberRows(list);
      }
      return;
    }
    var rmRow = t.closest(".bo-row__remove");
    if (rmRow) {
      var list2 = byId("bo-rows");
      if (list2 && list2.querySelectorAll(".bo-row").length > 1) { rmRow.closest(".bo-row").remove(); renumberRows(list2); }
      return;
    }

    // Універсальні таби
    var tab = t.closest("[data-tab]");
    if (tab && tab.getAttribute("data-tab-group")) { switchTab(tab.getAttribute("data-tab-group"), tab.getAttribute("data-tab")); return; }

    // 1. Дії за data-toggle / data-action / data-open / data-close
    var toggle = t.closest("[data-toggle]");
    if (toggle) {
      var what = toggle.getAttribute("data-toggle");
      if (what === "theme") { toggleTheme(); return; }
      if (what === "mega") { e.preventDefault(); toggleMega(); return; }
      if (what === "cart") { e.preventDefault(); toggleCart(); return; }
      if (what === "sort") { e.preventDefault(); toggleSort(toggle); return; }
    }

    var dd = t.closest("[data-dropdown]");
    if (dd) { e.preventDefault(); toggleDropdown(dd); return; }

    if (t.closest("[data-open='auth']")) { e.preventDefault(); openAuth(); return; }
    if (t.closest("[data-close]")) { closeAll(); closeAuth(); closeFilters(); return; }

    // 2. Мега-меню: вибір категорії
    var megaBackBtn = t.closest("[data-mega-back]");
    if (megaBackBtn) { megaBack(); return; }
    var megaCat = t.closest("[data-mega-cat]");
    if (megaCat) { selectMegaCat(megaCat.getAttribute("data-mega-cat")); return; }

    // 3. Сортування: вибір пункту
    var sortItem = t.closest("[data-sort-item]");
    if (sortItem) { pickSort(sortItem); return; }

    // 4. Перемикач вигляду
    var viewBtn = t.closest("[data-view]");
    if (viewBtn) { setView(viewBtn.getAttribute("data-view")); return; }

    // 5. Авторизація: таби
    var authTabBtn = t.closest("[data-auth-tab]");
    if (authTabBtn) { authTab(authTabBtn.getAttribute("data-auth-tab")); return; }

    // 6. Фільтри: мобільне відкриття/закриття
    if (t.closest("[data-filters-open]")) { openFilters(); return; }
    if (t.closest("[data-filters-close]") || t.id === "filters-backdrop") { closeFilters(); return; }
    if (t.closest("[data-filters-clear]")) { clearFilters(); return; }

    // 7. Чекбокси / розміри / кольори / тогл наявності
    var check = t.closest(".filters .check");
    if (check) { check.classList.toggle("is-checked"); return; }
    var sizeBtn = t.closest(".filters .size-btn");
    if (sizeBtn) { sizeBtn.classList.toggle("is-active"); return; }
    var swatch = t.closest(".filters .swatch");
    if (swatch) { swatch.classList.toggle("is-active"); return; }
    var tgl = t.closest(".filters .toggle");
    if (tgl) { tgl.classList.toggle("is-on"); return; }

    // 8. Активні чипи — прибрати
    var chip = t.closest("[data-chip]");
    if (chip) { chip.remove(); return; }

    // 9. Картка товару: обране / в кошик
    var wish = t.closest(".product-card__wish");
    if (wish) {
      e.preventDefault();
      wish.classList.toggle("is-active");
      toast("info", wish.classList.contains("is-active") ? "Додано в обране" : "Прибрано з обраного");
      return;
    }
    var add = t.closest(".product-card__add");
    if (add) { e.preventDefault(); toast("success", "Товар додано в кошик"); return; }

    // 10. Клік повз — закрити дропдауни/меню (не панель кошика/модалку)
    if (!t.closest(".dropdown") && !t.closest("#sort-menu") && !t.closest(".sort")
        && !t.closest("#search-results") && !t.closest(".search")) {
      document.querySelectorAll(".dropdown.is-open").forEach(function (d) { d.classList.remove("is-open"); });
      document.querySelectorAll(".dropdown [data-popover]").forEach(hide);
      hide(byId("sort-menu"));
    }
  });

  /* ---------- Оверлеї-затемнення закривають свої шари ---------- */
  document.addEventListener("click", function (e) {
    if (e.target.classList && e.target.classList.contains("overlay")) {
      closeAll(); closeAuth();
    }
  });

  /* ---------- Поле пошуку ---------- */
  document.addEventListener("input", function (e) {
    var input = e.target.closest("[data-search-input]");
    if (input) onSearchInput(input);
  });

  /* ---------- Очищення поля пошуку ---------- */
  document.addEventListener("click", function (e) {
    var btn = e.target.closest("[data-search-clear]");
    if (!btn) return;
    var wrap = btn.closest(".search");
    var input = wrap && wrap.querySelector("[data-search-input]");
    if (input) { input.value = ""; input.focus(); onSearchInput(input); }
  });

  /* ---------- Повзунок ціни ---------- */
  document.addEventListener("input", function (e) {
    var range = e.target.closest("[data-price]");
    if (range) {
      var label = document.querySelector("[data-price-label]");
      if (label) label.textContent = Number(range.value).toLocaleString("uk-UA") + " ₴";
    }
  });

  /* ---------- Сабміт форм (демо) ---------- */
  document.addEventListener("submit", function (e) {
    var form = e.target.closest("[data-form]");
    if (!form) return;
    e.preventDefault();
    if (applyStep(form)) return;
    var kind = form.getAttribute("data-form");
    if (kind === "subscribe") { toast("success", "Дякуємо! Підписку оформлено"); form.reset(); }
    else if (kind === "auth") { closeAuth(); toast("success", "Вітаємо! Ви увійшли в акаунт"); }
    else if (kind === "search") { toast("info", "Виконуємо пошук…"); }
    else if (kind === "review") { closeAll(); toast("success", "Дякуємо! Відгук надіслано на модерацію"); }
    else if (kind === "oneclick") { closeAll(); toast("success", "Дякуємо! Менеджер передзвонить"); }
    else if (kind === "promo") { toast("success", "Промокод застосовано: -10%"); }
    else if (kind === "checkout") { window.location.href = "order-success.html"; }
    else if (kind === "copy") { toast("success", "Посилання скопійовано"); }
    else { toast("success", "Готово!"); }
  });

  /* ---------- Esc закриває все ---------- */
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") { closeAll(); closeAuth(); closeFilters(); }
  });

  /* ---------- Калькулятор доставки (жива UI-логіка) ---------- */
  function fmtUAH(n) { return Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ") + " ₴"; }
  function calcGroupVal(group) {
    var el = document.querySelector('.calc-opt.is-active[data-calc-group="' + group + '"]');
    return el ? el.getAttribute("data-calc-val") : "";
  }
  function recalc() {
    var root = byId("calc");
    if (!root) return;
    var currency = calcGroupVal("currency") || "usd";
    var method = calcGroupVal("method") || "air";
    var rate = currency === "usd" ? 41.5 : 44;
    var value = parseFloat((root.querySelector("[data-calc-value]") || {}).value) || 0;
    var qty = Math.max(1, parseInt((root.querySelector("[data-calc-qty]") || {}).value) || 1);
    var weight = Math.max(0, parseFloat((root.querySelector("[data-calc-weight]") || {}).value) || 0);
    var goods = value * rate * qty;
    var perKg = method === "air" ? 560 : (method === "sea" ? 200 : 120);
    var delivery = Math.round(perKg * weight);
    var commission = goods * 0.10;
    var valueEUR = goods / 44;
    var over = Math.max(0, valueEUR - 150);
    var customs = over * 0.30 * 44;
    var total = goods + delivery + commission + customs;
    var set = function (sel, txt, color) { var el = root.querySelector(sel); if (el) { el.textContent = txt; if (color) el.style.color = color; } };
    set("[data-calc-total]", fmtUAH(total));
    set("[data-calc-goods]", fmtUAH(goods));
    set("[data-calc-delivery]", fmtUAH(delivery), "var(--text)");
    set("[data-calc-commission]", fmtUAH(commission));
    set("[data-calc-customs]", customs <= 0 ? "Немає" : fmtUAH(customs), customs <= 0 ? "var(--text)" : "var(--text)");
    var eurRound = Math.round(valueEUR);
    set("[data-calc-note]", valueEUR <= 150
      ? ("Сума " + eurRound + " € — в межах ліміту 150 €. Митних платежів немає.")
      : ("Сума " + eurRound + " € перевищує ліміт 150 €. Мито нараховується на " + Math.round(over) + " € перевищення."));
  }
  document.addEventListener("click", function (e) {
    var opt = e.target.closest(".calc-opt");
    if (opt) {
      var g = opt.getAttribute("data-calc-group");
      document.querySelectorAll('.calc-opt[data-calc-group="' + g + '"]').forEach(function (o) { o.classList.remove("is-active"); });
      opt.classList.add("is-active");
      // регіон → синхронізувати валюту
      if (g === "region") {
        var cur = opt.getAttribute("data-calc-val") === "eu" ? "eur" : "usd";
        document.querySelectorAll('.calc-opt[data-calc-group="currency"]').forEach(function (o) { o.classList.toggle("is-active", o.getAttribute("data-calc-val") === cur); });
      }
      recalc();
    }
    if (e.target.closest("[data-calc-run]")) { recalc(); toast("success", "Вартість розраховано"); }
  });
  document.addEventListener("input", function (e) {
    if (e.target.closest("[data-calc-value],[data-calc-weight],[data-calc-qty]")) recalc();
  });
  document.addEventListener("change", function (e) {
    if (e.target.closest("[data-calc-category]")) recalc();
  });

  /* ---------- Калькулятор мита (HowItWorks) ---------- */
  function recalcHiw() {
    var root = byId("hiw-calc");
    if (!root) return;
    var modeEl = document.querySelector(".hiw-mode.is-active");
    var mode = modeEl ? modeEl.getAttribute("data-hiw-mode") : "air";
    var v = parseFloat((root.querySelector("[data-hiw-value]") || {}).value) || 0;
    var shipMap = { air: 9, sea: 5, road: 6 };
    var ship = shipMap[mode] || 9;
    var excess = Math.max(0, v - 150);
    var duty = excess > 0 ? (excess * 0.10 + (excess + excess * 0.10) * 0.20) : 0;
    var total = v + ship + duty;
    var eur = function (n) { return "€" + (Math.round(n * 100) / 100).toLocaleString("uk-UA"); };
    var set = function (sel, txt) { var el = root.querySelector(sel); if (el) el.textContent = txt; };
    set("[data-hiw-goods]", eur(v));
    set("[data-hiw-ship]", eur(ship));
    set("[data-hiw-duty]", eur(duty));
    set("[data-hiw-total]", eur(total));
    set("[data-hiw-note]", excess > 0 ? "(понад €150)" : "(не потрібні)");
  }
  document.addEventListener("click", function (e) {
    var m = e.target.closest(".hiw-mode");
    if (m) { document.querySelectorAll(".hiw-mode").forEach(function (b) { b.classList.remove("is-active"); }); m.classList.add("is-active"); recalcHiw(); }
  });
  document.addEventListener("input", function (e) { if (e.target.closest("[data-hiw-value]")) recalcHiw(); });

  /* ---------- Конфеті (сторінка успіху) ---------- */
  function launchConfetti() {
    if (window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches) return;
    var c = document.createElement("div");
    c.setAttribute("aria-hidden", "true");
    c.style.cssText = "position:fixed;inset:0;pointer-events:none;z-index:9999;overflow:hidden;";
    var colors = ["#E85A3C", "#2E8B57", "#2C6FB0", "#B07914", "#7360F2", "#EC4899"];
    for (var i = 0; i < 64; i++) {
      var p = document.createElement("div");
      var size = 6 + Math.random() * 7;
      p.style.cssText = "position:absolute;top:-24px;left:" + (Math.random() * 100) + "%;width:" + size + "px;height:" + (size * 1.4) + "px;background:" + colors[Math.floor(Math.random() * colors.length)] + ";opacity:.9;border-radius:" + (Math.random() > 0.5 ? "50%" : "2px") + ";animation:bt-confetti " + (2 + Math.random() * 2).toFixed(2) + "s " + (Math.random() * 0.6).toFixed(2) + "s ease-in forwards;";
      c.appendChild(p);
    }
    document.body.appendChild(c);
    setTimeout(function () { if (c.parentNode) c.parentNode.removeChild(c); }, 4400);
  }
  /* ---------- Checkout: автодоповнення міста / відділення ---------- */
  var AC_CITIES = [
    { name: "Київ", region: "Київська область" }, { name: "Харків", region: "Харківська область" },
    { name: "Одеса", region: "Одеська область" }, { name: "Дніпро", region: "Дніпропетровська область" },
    { name: "Львів", region: "Львівська область" }, { name: "Запоріжжя", region: "Запорізька область" },
    { name: "Кривий Ріг", region: "Дніпропетровська область" }, { name: "Миколаїв", region: "Миколаївська область" },
    { name: "Вінниця", region: "Вінницька область" }, { name: "Полтава", region: "Полтавська область" },
    { name: "Чернігів", region: "Чернігівська область" }, { name: "Черкаси", region: "Черкаська область" },
    { name: "Житомир", region: "Житомирська область" }, { name: "Суми", region: "Сумська область" },
    { name: "Хмельницький", region: "Хмельницька область" }, { name: "Чернівці", region: "Чернівецька область" },
    { name: "Рівне", region: "Рівненська область" }, { name: "Івано-Франківськ", region: "Івано-Франківська область" },
    { name: "Тернопіль", region: "Тернопільська область" }, { name: "Луцьк", region: "Волинська область" },
    { name: "Ужгород", region: "Закарпатська область" }
  ];
  var AC_BRANCHES = [
    { name: "Відділення №1", addr: "вул. Пирогова, 135/1" }, { name: "Відділення №12", addr: "вул. Хрещатик, 22" },
    { name: "Відділення №25", addr: "просп. Перемоги, 67" }, { name: "Відділення №43", addr: "вул. В. Васильківська, 100" },
    { name: "Відділення №54", addr: "вул. Сагайдачного, 12" }, { name: "Відділення №77", addr: "Оболонська наб., 7" },
    { name: "Відділення №110", addr: "просп. Бажана, 16" }, { name: "Поштомат №3801", addr: "ТРЦ Gulliver, Спортивна пл., 1" },
    { name: "Поштомат №4120", addr: "вул. Антоновича, 50" }, { name: "Поштомат №5302", addr: "вул. Лаврська, 20" }
  ];
  var AC_PIN = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>';
  var AC_BOX = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M9 21v-4h6v4"/><path d="M9 7h.01M15 7h.01M9 11h.01M15 11h.01"/></svg>';
  function acEsc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" }[c]; }); }
  function acHl(text, q) {
    q = (q || "").trim();
    if (!q) return acEsc(text);
    var lo = text.toLowerCase(), idx = lo.indexOf(q.toLowerCase());
    if (idx < 0) return acEsc(text);
    return acEsc(text.slice(0, idx)) + '<span class="co-ac__hl">' + acEsc(text.slice(idx, idx + q.length)) + "</span>" + acEsc(text.slice(idx + q.length));
  }
  function acRender(input) {
    var kind = input.getAttribute("data-ac");
    var wrap = input.closest(".co-input-wrap");
    var list = wrap && wrap.querySelector("[data-ac-list]");
    if (!list) return;
    var q = input.value.trim().toLowerCase();
    var icon = kind === "city" ? AC_PIN : AC_BOX;
    var html = "";
    if (kind === "city") {
      var cm = (q ? AC_CITIES.filter(function (c) { return c.name.toLowerCase().indexOf(q) > -1; })
        .sort(function (a, b) { return a.name.toLowerCase().indexOf(q) - b.name.toLowerCase().indexOf(q); }) : AC_CITIES).slice(0, 6);
      if (!cm.length) html = '<div class="co-ac__empty">Місто не знайдено</div>';
      else cm.forEach(function (c) {
        html += '<button type="button" class="co-ac__item" data-ac-pick="' + acEsc(c.name) + '"><span class="co-ac__icon">' + icon + '</span><span class="co-ac__main"><span class="co-ac__name">' + acHl(c.name, q) + '</span><span class="co-ac__sub">' + acEsc(c.region) + "</span></span></button>";
      });
    } else {
      var bm = (q ? AC_BRANCHES.filter(function (b) { return (b.name + " " + b.addr).toLowerCase().indexOf(q) > -1; }) : AC_BRANCHES).slice(0, 6);
      if (!bm.length) html = '<div class="co-ac__empty">Нічого не знайдено</div>';
      else bm.forEach(function (b) {
        html += '<button type="button" class="co-ac__item" data-ac-pick="' + acEsc(b.name) + '"><span class="co-ac__icon">' + icon + '</span><span class="co-ac__main"><span class="co-ac__name">' + acHl(b.name, q) + '</span><span class="co-ac__sub">' + acHl(b.addr, q) + "</span></span></button>";
      });
    }
    list.innerHTML = html;
    list.hidden = false;
  }
  document.addEventListener("input", function (e) {
    var input = e.target.closest("[data-ac]");
    if (input) acRender(input);
  });
  document.addEventListener("focusin", function (e) {
    var input = e.target.closest("[data-ac]");
    if (input) acRender(input);
  });
  document.addEventListener("mousedown", function (e) {
    var pick = e.target.closest("[data-ac-pick]");
    if (!pick) return;
    e.preventDefault();
    var wrap = pick.closest(".co-input-wrap");
    var input = wrap && wrap.querySelector("[data-ac]");
    var list = wrap && wrap.querySelector("[data-ac-list]");
    if (input) input.value = pick.getAttribute("data-ac-pick");
    if (list) list.hidden = true;
  });
  document.addEventListener("focusout", function (e) {
    var input = e.target.closest("[data-ac]");
    if (!input) return;
    var wrap = input.closest(".co-input-wrap");
    var list = wrap && wrap.querySelector("[data-ac-list]");
    setTimeout(function () { if (list) list.hidden = true; }, 150);
  });

  function onReady() {
    if (document.body && document.body.hasAttribute("data-confetti")) launchConfetti();
    recalc();
    recalcHiw();
  }
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", onReady);
  else onReady();
})();
