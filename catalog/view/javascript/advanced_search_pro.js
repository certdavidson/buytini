/* Advanced Search Pro — autocomplete widget */
(function () {
    'use strict';

    var HISTORY_KEY = 'asp_history';
    var HISTORY_MAX = 10;

    /* ------------------------------------------------------------------ */
    /* History helpers (localStorage)                                       */
    /* ------------------------------------------------------------------ */
    function historyLoad() {
        try {
            var raw = localStorage.getItem(HISTORY_KEY);
            if (raw) {
                var arr = JSON.parse(raw);
                if (Array.isArray(arr)) return arr;
            }
        } catch (e) {}
        return [];
    }

    function historySave(items) {
        try { localStorage.setItem(HISTORY_KEY, JSON.stringify(items)); } catch (e) {}
    }

    function historyAdd(query) {
        query = String(query).trim();
        if (!query) return;
        var items = historyLoad();
        items = items.filter(function (q) { return q !== query; });
        items.unshift(query);
        historySave(items.slice(0, HISTORY_MAX));
    }

    function historyClear() {
        historySave([]);
    }

    function historyRemove(query) {
        historySave(historyLoad().filter(function (q) { return q !== query; }));
    }

    /* ------------------------------------------------------------------ */
    /* Recently-viewed products (localStorage — IDs only)                   */
    /* ------------------------------------------------------------------ */
    var VIEWED_KEY = 'asp_viewed';
    var VIEWED_MAX = 12;

    function viewedLoad() {
        try {
            var arr = JSON.parse(localStorage.getItem(VIEWED_KEY) || '[]');
            if (Array.isArray(arr)) return arr.map(function (n) { return parseInt(n, 10); }).filter(Boolean);
        } catch (e) {}
        return [];
    }

    function viewedAdd(id) {
        id = parseInt(id, 10);
        if (!id) return;
        var ids = viewedLoad().filter(function (n) { return n !== id; });
        ids.unshift(id);
        try { localStorage.setItem(VIEWED_KEY, JSON.stringify(ids.slice(0, VIEWED_MAX))); } catch (e) {}
    }

    /* Capture the product currently being viewed so it can resurface in the
       "recently viewed" strip. The product form carries a hidden
       input[name=product_id] with the current id; listing pages only have
       empty/value-less ones (notify / quick-order widgets), so we take the
       first input that actually holds a numeric id. Theme-agnostic (no reliance
       on #button-cart, which the buytini theme renames). */
    function captureViewed(pidAttr) {
        var pid = parseInt(pidAttr, 10);   // authoritative — emitted by the widget controller from the route
        if (!pid) {                         // fallback for themes/setups where the attr is empty
            var inps = document.querySelectorAll('input[name="product_id"]');
            for (var i = 0; i < inps.length; i++) {
                var v = parseInt(inps[i].value, 10);
                if (v) { pid = v; break; }
            }
        }
        if (pid) viewedAdd(pid);
    }

    /* ------------------------------------------------------------------ */
    /* Widget initialiser                                                   */
    /* ------------------------------------------------------------------ */
    function init(container) {
        var results  = container.querySelector('[data-asp-results]');
        if (!results) return;
        var overlay   = container.querySelector('[data-asp-overlay]');
        var stub      = container.querySelector('[data-asp-stub]');
        var toggleBtn = document.getElementById('search-toggle');
        var blockEl   = document.getElementById('search-block');

        /* Layout: 'popup' (detached overlay, header is a stub) or 'inline'
           (the header field IS the search input; results drop in-place). */
        var inlineMode = (container.dataset.aspLayout === 'inline');
        if (inlineMode) { container.classList.add('asp-mode-inline'); }

        /* Popup mode only: detach popup + overlay to <body> so a header
           overflow/transform context can never clip the fixed-positioned panel.
           Inline mode keeps everything in the container (the head is the bar). */
        if (!inlineMode) {
            if (results.parentNode !== document.body) document.body.appendChild(results);
            if (overlay && overlay.parentNode !== document.body) document.body.appendChild(overlay);
        }

        var input    = results.querySelector('[data-asp-input]');
        var list     = results.querySelector('[data-asp-list]');
        var empty    = results.querySelector('[data-asp-empty]');
        var footer   = results.querySelector('[data-asp-footer]');
        var viewAll  = results.querySelector('[data-asp-view-all]');
        var loading  = results.querySelector('[data-asp-loading]');
        var popularWrap = results.querySelector('[data-asp-popular-tags-wrap]');
        var facetsWrap  = results.querySelector('[data-asp-facets]');
        var clearBtn    = results.querySelector('[data-asp-clear]');
        var closeBtn    = results.querySelector('[data-asp-pop-close]');
        var endpoint = container.querySelector('[data-asp-endpoint]').value;
        var minChars = parseInt(container.querySelector('[data-asp-min]').value, 10) || 2;
        var delay    = parseInt(container.querySelector('[data-asp-delay]').value, 10) || 180;

        /* Read i18n strings from data attributes */
        var i18n = {
            noResults:    container.dataset.aspI18nNoResults   || 'No results',
            recent:       container.dataset.aspI18nRecent      || 'Recent',
            clear:        container.dataset.aspI18nClear       || 'Clear',
            inStock:      container.dataset.aspI18nInStock     || 'In stock',
            outOfStock:   container.dataset.aspI18nOutOfStock  || 'Out of stock',
            category:     container.dataset.aspI18nCategory    || 'Category',
            brand:        container.dataset.aspI18nBrand       || 'Brand',
            popular:      container.dataset.aspI18nPopular     || 'Popular',
            sale:         container.dataset.aspI18nSale        || 'Sale',
            didYouMean:   container.dataset.aspI18nDidYouMean  || 'Did you mean',
            voiceListen:  container.dataset.aspI18nVoiceListen || 'Listening…',
            popularTags:  container.dataset.aspI18nPopularTags || 'Popular',
            idLabel:      container.dataset.aspI18nId          || 'ID',
            modelLabel:   container.dataset.aspI18nModel       || 'SKU',
            popularQueries:  container.dataset.aspI18nPopularQueries  || 'Популярні запити:',
            popularProducts: container.dataset.aspI18nPopularProducts || 'Популярні товари:',
            popularBrands:   container.dataset.aspI18nPopularBrands   || 'Популярні бренди',
            addToCart:       container.dataset.aspI18nAddToCart       || 'У кошик',
            allCategories:   container.dataset.aspI18nAllCategories   || 'Всі категорії',
            otherCategory:   container.dataset.aspI18nOtherCategory   || 'Інше',
            viewed:          container.dataset.aspI18nViewed          || 'Переглянуті товари:',
            searchHistory:   container.dataset.aspI18nSearchHistory   || 'Історія запитів',
            more:            container.dataset.aspI18nMore            || 'Ще',
            openCategory:    container.dataset.aspI18nOpenCategory    || 'Відкрити категорію',
            sortCheap:       container.dataset.aspI18nSortCheap       || 'Спочатку дешевші',
            sortExpensive:   container.dataset.aspI18nSortExpensive   || 'Спочатку дорожчі',
            sortDefault:     container.dataset.aspI18nSortDefault     || 'Типово'
        };

        var timer          = null;
        var lastController = null;

        var isOpen         = false;

        /* ── DOM helpers ── */
        function show(el) { if (el) el.style.display = ''; }
        function hide(el) { if (el) el.style.display = 'none'; }

        /* Position the detached popup: full-screen sheet on mobile, wide
           (up to 80vw) anchored panel on desktop. */
        function positionPopup() {
            var vw = document.documentElement.clientWidth;
            var vh = window.innerHeight || document.documentElement.clientHeight;
            if (vw <= 768) {
                results.style.position = 'fixed';
                results.style.top    = '0px';
                results.style.left   = '0px';
                results.style.width  = vw + 'px';
                results.style.height = vh + 'px';
                return;
            }
            results.style.height = '';
            var anchor = (stub && stub.offsetParent !== null) ? stub : (blockEl || stub);
            var r = (anchor || container).getBoundingClientRect();
            var width = Math.min(Math.round(vw * 0.8), vw - 24);
            var left = Math.round((vw - width) / 2);   /* centered horizontally */
            if (left < 12) left = 12;
            results.style.position = 'fixed';
            results.style.top   = Math.round(r.bottom + 8) + 'px';
            results.style.left  = left + 'px';
            results.style.width = width + 'px';
        }

        function openResults() {
            if (!isOpen && !inlineMode) {
                positionPopup();
                if (overlay) overlay.style.display = 'block';
                document.body.classList.add('asp-pop-open');
            }
            results.classList.add('open');
            isOpen = true;
        }

        function closeResults() {
            results.classList.remove('open');
            if (!inlineMode) {
                if (overlay) overlay.style.display = 'none';
                document.body.classList.remove('asp-pop-open');
            }
            isOpen = false;
            if (facetsWrap) { facetsWrap.innerHTML = ''; hide(facetsWrap); }
            if (popularWrap) { popularWrap.innerHTML = ''; hide(popularWrap); }
        }

        /* Stub click → open the detached popup (empty-state or live results). */
        function openPopup() {
            var q = input.value.trim();
            if (q.length >= minChars) { fetchItems(q); } else { showHistory(); }
            setTimeout(function () { try { input.focus(); } catch (e) {} }, 30);
        }

        function closePopup() {
            clearTimeout(timer);
            if (lastController) { lastController.abort(); lastController = null; }
            closeResults();
            try { input.blur(); } catch (e) {}
        }

        function setLoading(on) {
            if (!loading) return;
            if (on) {
                loading.removeAttribute('aria-hidden');
                loading.classList.add('asp-loading-active');
            } else {
                loading.setAttribute('aria-hidden', 'true');
                loading.classList.remove('asp-loading-active');
            }
        }

        /* ── Result items ── */
        function renderFacets(facets) {
            if (!facetsWrap) return;
            if (!facets || !facets.length) {
                facetsWrap.innerHTML = '';
                hide(facetsWrap);
                return;
            }
            facetsWrap.innerHTML = '';
            facets.forEach(function (f) {
                var chip = document.createElement('a');
                chip.className = 'asp-facet-chip asp-facet-' + f.type;
                chip.href = (f.href || '#').replace(/&amp;/g, '&');
                chip.textContent = f.name + ' (' + f.count + ')';
                facetsWrap.appendChild(chip);
            });
            show(facetsWrap);
        }

        function renderItems(items, didYouMean, facets) {
            list.innerHTML = '';
            renderFacets(facets);

            if (!items || !items.length) {
                show(empty);
                empty.innerHTML = '';
                var noResultsText = document.createTextNode(i18n.noResults);
                empty.appendChild(noResultsText);
                if (didYouMean && didYouMean.query) {
                    var dym = document.createElement('div');
                    dym.className = 'asp-did-you-mean';
                    dym.appendChild(document.createTextNode(i18n.didYouMean + ': '));
                    var dymLink = document.createElement('a');
                    dymLink.href = (didYouMean.search_url || '#').replace(/&amp;/g, '&');
                    dymLink.textContent = didYouMean.query;
                    dym.appendChild(dymLink);
                    empty.appendChild(dym);
                }
                hide(footer);
                return;
            }

            hide(empty);
            show(footer);

            /* ── find-iq layout: category sidebar + grouped product cards ── */
            var products = [], suggestions = [];
            items.forEach(function (it) {
                if (it.product_id && it.type !== 'category' && it.type !== 'brand' && it.type !== 'query') {
                    products.push(it);
                } else {
                    suggestions.push(it);
                }
            });

            var otherLabel = i18n.otherCategory || '—';
            var groups = [], gIndex = {};
            products.forEach(function (p) {
                var cn = (p.category && p.category.name) || otherLabel;
                if (!(cn in gIndex)) {
                    gIndex[cn] = groups.length;
                    groups.push({ name: cn, id: (p.category && p.category.id) || 0, items: [] });
                }
                groups[gIndex[cn]].items.push(p);
            });

            var sr = document.createElement('div');
            sr.className = 'asp-sr';

            /* Sidebar */
            var side = document.createElement('div');
            side.className = 'asp-side';
            var groupsWrap = document.createElement('div');
            groupsWrap.className = 'asp-groups' + (groups.length > 1 ? ' asp-multi' : '');

            function catHref(id) { return 'index.php?route=product/category&path=' + id; }

            function makeSideItem(label, count, catName) {
                var b = document.createElement('button');
                b.type = 'button';
                b.className = 'asp-side-item' + (catName === '' ? ' active' : '');
                var nm = document.createElement('span'); nm.className = 'asp-side-name'; nm.textContent = label; b.appendChild(nm);
                var ct = document.createElement('span'); ct.className = 'asp-side-count'; ct.textContent = count; b.appendChild(ct);
                b.addEventListener('click', function () {
                    var actives = side.querySelectorAll('.asp-side-item');
                    for (var i = 0; i < actives.length; i++) { actives[i].classList.remove('active'); }
                    b.classList.add('active');
                    var single = catName !== '';
                    groupsWrap.classList.toggle('asp-multi', !single && groups.length > 1);
                    var gs = groupsWrap.querySelectorAll('.asp-group');
                    for (var j = 0; j < gs.length; j++) {
                        var match = gs[j].getAttribute('data-cat') === catName;
                        var on = !single || match;
                        gs[j].style.display = on ? '' : 'none';
                        gs[j].classList.toggle('asp-show-foot', single && match);
                        gs[j].classList.toggle('asp-cat-mode', single && match);
                        if (gs[j]._setMode) gs[j]._setMode(single && match);
                    }
                });
                return b;
            }
            side.appendChild(makeSideItem(i18n.allCategories || 'Всі категорії', products.length, ''));
            groups.forEach(function (g) { side.appendChild(makeSideItem(g.name, g.items.length, g.name)); });

            /* Grouped product cards */
            groups.forEach(function (g) {
                var grp = document.createElement('div');
                grp.className = 'asp-group';
                grp.setAttribute('data-cat', g.name);

                var head = document.createElement('div');
                head.className = 'asp-cat-head';
                var hn = document.createElement('span'); hn.className = 'asp-cat-name'; hn.textContent = g.name; head.appendChild(hn);
                var hr = document.createElement('span'); hr.className = 'asp-cat-right';
                if (g.id) {
                    var more = document.createElement('a'); more.className = 'asp-cat-more';
                    more.href = catHref(g.id);
                    more.textContent = i18n.more + ' ' + g.items.length + ' →';
                    more.addEventListener('click', function (e) { e.stopPropagation(); });
                    hr.appendChild(more);
                }
                var chev = document.createElement('span'); chev.className = 'asp-cat-chev'; chev.appendChild(svgChevron());
                hr.appendChild(chev);
                head.appendChild(hr);
                /* Accordion toggle — only on mobile (desktop uses the sidebar). */
                head.addEventListener('click', function (e) {
                    if (e.target.closest && e.target.closest('.asp-cat-more')) return;
                    if (window.innerWidth > 768) return;
                    grp.classList.toggle('asp-group--collapsed');
                });
                grp.appendChild(head);

                var cards = document.createElement('div');
                cards.className = 'asp-sr-cards';
                g.items.forEach(function (p) { cards.appendChild(buildProductCard(p, true, true)); });
                grp.appendChild(cards);

                /* Category actions: sort + open-category — shown only when this
                   category is selected (desktop sidebar / mobile expanded group). */
                if (g.id) {
                    var gfoot = document.createElement('div'); gfoot.className = 'asp-group-foot';
                    var sortWrap = document.createElement('div'); sortWrap.className = 'asp-sort';
                    sortWrap.appendChild(makeSortSelect(cards));
                    gfoot.appendChild(sortWrap);
                    var openA = document.createElement('a'); openA.className = 'asp-open-cat';
                    openA.href = catHref(g.id); openA.textContent = i18n.openCategory + ' →';
                    gfoot.appendChild(openA);
                    grp.appendChild(gfoot);
                }

                /* Category-mode pager: 12 cards / page in a 2-column grid. */
                (function () {
                    var PAGE = 12, page = 0, catMode = false;
                    var pager = document.createElement('div'); pager.className = 'asp-pager';
                    function pBtn(cls) {
                        var x = document.createElement('button'); x.type = 'button';
                        x.className = 'asp-pager-btn ' + cls; x.appendChild(svgChevron()); return x;
                    }
                    var prev = pBtn('asp-pager-prev'), ind = document.createElement('span'), next = pBtn('asp-pager-next');
                    ind.className = 'asp-pager-ind';
                    pager.appendChild(prev); pager.appendChild(ind); pager.appendChild(next);
                    grp.appendChild(pager);
                    function render() {
                        var cs = cards.children, total = cs.length, pages = Math.max(1, Math.ceil(total / PAGE));
                        if (page >= pages) page = pages - 1;
                        for (var i = 0; i < total; i++) {
                            cs[i].style.display = (!catMode || (i >= page * PAGE && i < page * PAGE + PAGE)) ? '' : 'none';
                        }
                        pager.style.display = (catMode && total > PAGE) ? '' : 'none';
                        ind.textContent = (page + 1) + ' / ' + pages;
                        prev.disabled = page === 0; next.disabled = page >= pages - 1;
                    }
                    prev.addEventListener('click', function (e) { e.preventDefault(); if (page > 0) { page--; render(); } });
                    next.addEventListener('click', function (e) { e.preventDefault(); page++; render(); });
                    grp._setMode = function (on) { catMode = on; page = 0; render(); };
                }());

                groupsWrap.appendChild(grp);
            });

            if (groups.length) {
                sr.appendChild(side);
                sr.appendChild(groupsWrap);
            } else {
                /* Only category/brand suggestions and no products — show cards-less list */
                sr.appendChild(groupsWrap);
            }
            list.appendChild(sr);
        }

        function makeBadge(text, cls) {
            var el = document.createElement('span');
            el.className = 'asp-badge ' + cls;
            el.textContent = text;
            return el;
        }

        /* ── Empty-state dashboard: popular queries / products / brands ── */
        var dashEndpoint = endpoint.replace(/oc_kit_advanced_search\/live/, 'oc_kit_advanced_search/dashboard');
        var dashData = null;
        var dashLoaded = false;

        function svgCart() {
            var s = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            s.setAttribute('viewBox', '0 0 19 19'); s.setAttribute('class', 'asp-svg-cart');
            var p = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            p.setAttribute('d', 'M15.04 5.54h-2.37v-.79a3.17 3.17 0 0 0-6.34 0v.79H3.96a.79.79 0 0 0-.79.79v8.71a2.37 2.37 0 0 0 2.37 2.37h7.92a2.37 2.37 0 0 0 2.37-2.37V6.33a.79.79 0 0 0-.79-.79Zm-7.12-.79a1.58 1.58 0 0 1 3.16 0v.79H7.92v-.79Zm6.33 10.29a.79.79 0 0 1-.79.79H5.54a.79.79 0 0 1-.79-.79V7.12h1.58v.79a.79.79 0 1 0 1.59 0v-.79h3.16v.79a.79.79 0 1 0 1.59 0v-.79h1.58v7.92Z');
            s.appendChild(p); return s;
        }
        function svgTag() {
            var s = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            s.setAttribute('viewBox', '0 0 15 15'); s.setAttribute('class', 'asp-svg-tag');
            var p = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            p.setAttribute('d', 'M.31 0h5.63c.08 0 .16.03.22.09l8.75 8.75a.44.44 0 0 1 0 .44l-5.63 5.63a.32.32 0 0 1-.44 0L.09 6.16A.32.32 0 0 1 0 5.94V.31C0 .14.14 0 .31 0Zm3.13 5a1.56 1.56 0 1 0 0-3.13 1.56 1.56 0 0 0 0 3.13Z');
            s.appendChild(p); return s;
        }
        function svgX() {
            var s = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            s.setAttribute('viewBox', '0 0 24 24'); s.setAttribute('class', 'asp-svg-x');
            s.setAttribute('fill', 'none'); s.setAttribute('stroke', 'currentColor'); s.setAttribute('stroke-width', '2'); s.setAttribute('stroke-linecap', 'round');
            var p = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            p.setAttribute('d', 'M18 6 6 18M6 6l12 12');
            s.appendChild(p); return s;
        }
        function svgTrash() {
            var s = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            s.setAttribute('viewBox', '0 0 24 24'); s.setAttribute('class', 'asp-svg-trash');
            s.setAttribute('fill', 'none'); s.setAttribute('stroke', 'currentColor'); s.setAttribute('stroke-width', '2'); s.setAttribute('stroke-linecap', 'round'); s.setAttribute('stroke-linejoin', 'round');
            var p = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            p.setAttribute('d', 'M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6');
            s.appendChild(p); return s;
        }
        function svgChevron() {
            var s = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            s.setAttribute('viewBox', '0 0 24 24'); s.setAttribute('class', 'asp-svg-chev');
            s.setAttribute('fill', 'none'); s.setAttribute('stroke', 'currentColor'); s.setAttribute('stroke-width', '2.2'); s.setAttribute('stroke-linecap', 'round'); s.setAttribute('stroke-linejoin', 'round');
            var p = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            p.setAttribute('d', 'M6 9l6 6 6-6');
            s.appendChild(p); return s;
        }

        /* Price-sort <select> for a category's card list (default / asc / desc). */
        function makeSortSelect(cardsEl) {
            var orig = Array.prototype.slice.call(cardsEl.children);
            var sel = document.createElement('select');
            sel.className = 'asp-sort-select';
            [['', i18n.sortDefault], ['asc', i18n.sortCheap], ['desc', i18n.sortExpensive]].forEach(function (o) {
                var op = document.createElement('option'); op.value = o[0]; op.textContent = o[1]; sel.appendChild(op);
            });
            sel.addEventListener('change', function () {
                var dir = sel.value, nodes;
                if (!dir) { nodes = orig.slice(); }
                else {
                    nodes = orig.slice().sort(function (a, b) {
                        var pa = parseFloat(a.dataset.price), pb = parseFloat(b.dataset.price);
                        if (isNaN(pa)) pa = dir === 'asc' ? Infinity : -Infinity;
                        if (isNaN(pb)) pb = dir === 'asc' ? Infinity : -Infinity;
                        return dir === 'asc' ? pa - pb : pb - pa;
                    });
                }
                nodes.forEach(function (n) { cardsEl.appendChild(n); });
            });
            return sel;
        }

        /* Parse a formatted price ("1 490 грн", "256.00 ₴") to a sortable number. */
        function priceNum(p) {
            var s = p.special || p.price;
            if (!s) return null;
            s = String(s).replace(/[^\d.,]/g, '');
            if (s.indexOf(',') > -1 && s.indexOf('.') > -1) { s = s.replace(/\./g, '').replace(',', '.'); }
            else if (s.indexOf(',') > -1) { s = s.replace(',', '.'); }
            var n = parseFloat(s);
            return isNaN(n) ? null : n;
        }

        function buildPriceEl(p) {
            var pr = document.createElement('div'); pr.className = 'asp-card-price';
            if (p.special) {
                var old = document.createElement('span'); old.className = 'asp-card-old'; old.textContent = p.price; pr.appendChild(old);
                var nw = document.createElement('span'); nw.className = 'asp-card-new'; nw.textContent = p.special; pr.appendChild(nw);
                if (p.discount_pct) { var dc = document.createElement('span'); dc.className = 'asp-card-disc'; dc.textContent = '-' + p.discount_pct + '%'; pr.appendChild(dc); }
            } else if (p.price) {
                pr.textContent = p.price;
            }
            return pr;
        }

        function buildCartBtn(p) {
            var cart = document.createElement('button');
            cart.type = 'button'; cart.className = 'asp-card-cart'; cart.setAttribute('aria-label', i18n.addToCart);
            cart.appendChild(svgCart());
            cart.addEventListener('click', function (e) { e.preventDefault(); e.stopPropagation(); aspAddToCart(p.product_id); });
            return cart;
        }

        /* Product card. rowMode → horizontal list row (search results);
           otherwise a vertical card (dashboard carousels). */
        function buildProductCard(p, useHl, rowMode) {
            var a = document.createElement('a');
            a.className = 'asp-card' + (rowMode ? ' asp-card--row' : '');
            a.href = (p.href || '#').replace(/&amp;/g, '&');
            var pn = priceNum(p);
            if (pn !== null) a.dataset.price = pn;
            var iw = document.createElement('div'); iw.className = 'asp-card-img';
            if (p.image) { var im = document.createElement('img'); im.src = p.image; im.alt = p.name || ''; im.loading = 'lazy'; iw.appendChild(im); }
            if (p.special) { var bd = document.createElement('span'); bd.className = 'asp-card-badge'; bd.textContent = i18n.sale; iw.appendChild(bd); }
            a.appendChild(iw);
            var nm = document.createElement('div'); nm.className = 'asp-card-name';
            if (useHl && p.name_hl) { nm.innerHTML = p.name_hl; } else { nm.textContent = p.name || ''; }
            if (rowMode) {
                var main = document.createElement('div'); main.className = 'asp-card-main';
                main.appendChild(nm); main.appendChild(buildPriceEl(p));
                a.appendChild(main);
                if (p.product_id) a.appendChild(buildCartBtn(p));
            } else {
                a.appendChild(nm);
                var foot = document.createElement('div'); foot.className = 'asp-card-foot';
                foot.appendChild(buildPriceEl(p));
                if (p.product_id) foot.appendChild(buildCartBtn(p));
                a.appendChild(foot);
            }
            return a;
        }

        /* Horizontal carousel (swipe + prev/next arrows) for dashboard products. */
        function buildCarousel(cards) {
            var wrap = document.createElement('div'); wrap.className = 'asp-dash-carousel-wrap';
            var car = document.createElement('div'); car.className = 'asp-dash-carousel';
            cards.forEach(function (p) { car.appendChild(buildProductCard(p, false)); });
            wrap.appendChild(car);
            function step() { var c = car.querySelector('.asp-card'); return (c ? c.offsetWidth + 14 : 200) * 1.5; }
            function mkArrow(cls, dir) {
                var b = document.createElement('button'); b.type = 'button'; b.className = 'asp-dash-arrow ' + cls;
                b.appendChild(svgChevron());
                b.addEventListener('click', function (e) { e.preventDefault(); e.stopPropagation(); car.scrollBy({ left: dir * step(), behavior: 'smooth' }); });
                return b;
            }
            /* Visible, styled scroll progress bar beside the arrows. */
            var track = document.createElement('div'); track.className = 'asp-dash-track';
            var thumb = document.createElement('div'); thumb.className = 'asp-dash-thumb';
            track.appendChild(thumb);
            function updateThumb() {
                var sw = car.scrollWidth, cw = car.clientWidth;
                if (sw <= cw) { track.style.display = 'none'; return; }
                track.style.display = '';
                var ratio = cw / sw;
                thumb.style.width = Math.max(12, ratio * 100) + '%';
                thumb.style.left = (car.scrollLeft / sw) * 100 + '%';
            }
            car.addEventListener('scroll', updateThumb, { passive: true });
            setTimeout(updateThumb, 0);

            var arrows = document.createElement('div'); arrows.className = 'asp-dash-arrows';
            arrows.appendChild(track);
            arrows.appendChild(mkArrow('asp-dash-prev', -1));
            arrows.appendChild(mkArrow('asp-dash-next', 1));
            wrap.appendChild(arrows);
            return wrap;
        }

        function fetchDashboard(cb) {
            if (dashLoaded) { cb(); return; }
            var vids = viewedLoad().slice(0, VIEWED_MAX).join(',');
            var url = dashEndpoint + (vids ? '&viewed=' + encodeURIComponent(vids) : '');
            // X-Requested-With marks this as AJAX so the SEO language module skips its
            // cookie-language 301 redirect (which points at /ru/index.php?route=… — a 404).
            fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (d) { dashData = (d && d.status === 'ok') ? d : null; dashLoaded = true; cb(); })
                .catch(function () { dashLoaded = true; cb(); });
        }

        function dashSection(title) {
            var s = document.createElement('div'); s.className = 'asp-dash-section';
            var h = document.createElement('div'); h.className = 'asp-dash-head'; h.textContent = title;
            s.appendChild(h); return s;
        }

        function renderDashboard() {
            if (!popularWrap) return;
            popularWrap.innerHTML = '';

            /* Popular queries/tags — the server decides off/manual/auto; each item
               is {text, href}. href set (manual tag with a URL) → navigate; else
               run it as a search. */
            var queries = (dashData && dashData.queries) || [];

            var products = (dashData && dashData.products) || [];
            var brands   = (dashData && dashData.brands)   || [];

            if (queries.length) {
                var s1 = dashSection(i18n.popularQueries);
                var ul = document.createElement('ul'); ul.className = 'asp-dash-chips';
                queries.forEach(function (q) {
                    var text = (q && typeof q === 'object') ? (q.text || '') : (q || '');
                    if (!text) return;
                    var href = (q && typeof q === 'object') ? (q.href || '') : '';
                    var li = document.createElement('li'); li.className = 'asp-dash-chip'; li.textContent = text;
                    li.addEventListener('click', function () {
                        if (href) { window.location.href = href; return; }
                        input.value = text; syncClear(); fetchItems(text);
                    });
                    ul.appendChild(li);
                });
                s1.appendChild(ul); popularWrap.appendChild(s1);
            }

            /* 2. Search history (localStorage) — chips with per-item + clear-all. */
            var hist = historyLoad();
            if (hist.length) {
                var sH = dashSection(i18n.searchHistory);
                var clrAll = document.createElement('button');
                clrAll.type = 'button'; clrAll.className = 'asp-dash-clear';
                clrAll.appendChild(svgTrash());
                var clrT = document.createElement('span'); clrT.textContent = i18n.clear; clrAll.appendChild(clrT);
                clrAll.addEventListener('click', function (e) { e.stopPropagation(); historyClear(); renderDashboard(); });
                sH.querySelector('.asp-dash-head').appendChild(clrAll);

                var hul = document.createElement('ul'); hul.className = 'asp-dash-chips asp-hist-chips';
                hist.slice(0, HISTORY_MAX).forEach(function (q) {
                    var li = document.createElement('li'); li.className = 'asp-dash-chip asp-hist-chip';
                    var t = document.createElement('span'); t.className = 'asp-hist-chip-t'; t.textContent = q;
                    t.addEventListener('click', function () { input.value = q; syncClear(); fetchItems(q); });
                    li.appendChild(t);
                    var x = document.createElement('button'); x.type = 'button'; x.className = 'asp-hist-x'; x.setAttribute('aria-label', i18n.clear);
                    x.appendChild(svgX());
                    x.addEventListener('click', function (e) { e.stopPropagation(); historyRemove(q); renderDashboard(); });
                    li.appendChild(x);
                    hul.appendChild(li);
                });
                sH.appendChild(hul); popularWrap.appendChild(sH);
            }

            /* 3. Recently-viewed products — top 3, 3-up grid (desktop) / swipe (mobile). */
            var viewed = (dashData && dashData.viewed) || [];
            if (viewed.length) {
                var sV = dashSection(i18n.viewed);
                var vlist = document.createElement('div'); vlist.className = 'asp-dash-viewed';
                viewed.slice(0, 3).forEach(function (p) { vlist.appendChild(buildProductCard(p, false, true)); });
                sV.appendChild(vlist); popularWrap.appendChild(sV);
            }

            if (products.length) {
                var s2 = dashSection(i18n.popularProducts);
                s2.appendChild(buildCarousel(products)); popularWrap.appendChild(s2);
            }

            if (brands.length) {
                var s3 = dashSection(i18n.popularBrands);
                var bul = document.createElement('ul'); bul.className = 'asp-dash-brands';
                brands.forEach(function (b) {
                    var li = document.createElement('li'); li.className = 'asp-dash-brand';
                    li.appendChild(svgTag());
                    var sp = document.createElement('span'); sp.textContent = b.name; li.appendChild(sp);
                    li.addEventListener('click', function () { window.location.href = b.href; });
                    bul.appendChild(li);
                });
                s3.appendChild(bul); popularWrap.appendChild(s3);
            }

            show(popularWrap);
        }

        function aspAddToCart(id) {
            /* Custom add-to-cart function from settings (dotted path, e.g. "cart.add"). */
            var path = (container.dataset.aspCartFn || '').trim();
            if (path) {
                var parts = path.split('.'), obj = window, ctx = window;
                for (var i = 0; i < parts.length && obj != null; i++) { ctx = obj; obj = obj[parts[i]]; }
                if (typeof obj === 'function') { obj.call(ctx, id, 1); return; }
            }
            if (window.cart && typeof window.cart.add === 'function') { window.cart.add(id, 1); return; }
            var fd = new FormData(); fd.append('product_id', id); fd.append('quantity', 1);
            fetch('index.php?route=checkout/cart/add', { method: 'POST', body: fd, credentials: 'same-origin' }).catch(function () {});
        }

        /* ── Fetch ── */
        function fetchItems(q) {
            if (lastController) lastController.abort();
            lastController = new AbortController();

            setLoading(true);
            hide(empty);
            if (popularWrap) { popularWrap.innerHTML = ''; hide(popularWrap); }

            fetch(endpoint + '&q=' + encodeURIComponent(q), { signal: lastController.signal, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (res) {
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    return res.json();
                })
                .then(function (data) {
                    setLoading(false);
                    if (data.status === 'ok') {
                        renderItems(data.items, data.did_you_mean, data.facets);
                        if (viewAll) viewAll.href = (data.search_url || '#').replace(/&amp;/g, '&');
                        openResults();
                    } else if (data.status === 'too_short') {
                        closeResults();
                    } else {
                        renderItems([], null, null);
                        openResults();
                    }
                })
                .catch(function (err) {
                    setLoading(false);
                    if (err && err.name === 'AbortError') return;
                    renderItems([], null, null);
                    openResults();
                });
        }

        function showHistory() {
            // Abort any in-flight search + pending debounce so a late response
            // can't repaint the search-state under the empty-state dashboard.
            clearTimeout(timer);
            if (lastController) { lastController.abort(); lastController = null; }
            setLoading(false);
            hide(empty);
            list.innerHTML = '';
            if (facetsWrap) { facetsWrap.innerHTML = ''; hide(facetsWrap); }
            if (footer) hide(footer);
            fetchDashboard(renderDashboard);
            openResults();
        }

        /* ── Enter submits the query, Escape closes ── */
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                var q = input.value.trim();
                if (q) {
                    historyAdd(q);
                    if (viewAll && viewAll.href && viewAll.href !== '#') {
                        window.location.href = viewAll.href;
                    }
                }
            } else if (e.key === 'Escape') {
                closePopup();
            }
        });

        /* ── Clear button (×) — visible only while the input has text ── */
        function syncClear() {
            if (clearBtn) clearBtn.style.display = input.value ? '' : 'none';
        }
        if (clearBtn) {
            clearBtn.addEventListener('click', function (e) {
                e.preventDefault();
                input.value = '';
                syncClear();
                showHistory();
                try { input.focus(); } catch (err) {}
            });
        }

        /* ── Input events ── */
        input.addEventListener('input', function () {
            var q = input.value.trim();
            syncClear();
            clearTimeout(timer);
            if (q.length < minChars) {
                showHistory();              /* keep popup open, fall back to empty-state */
                return;
            }
            timer = setTimeout(function () { fetchItems(q); }, delay);
        });

        input.addEventListener('blur', function () {
            var q = input.value.trim();
            if (q.length >= minChars) historyAdd(q);
        });

        /* ── Open / close wiring ── */
        if (inlineMode) {
            /* The header field is the live input — focusing it opens the dropdown. */
            input.addEventListener('focus', function () {
                var q = input.value.trim();
                if (q.length >= minChars) { fetchItems(q); } else { showHistory(); }
            });
        } else if (stub) {
            stub.addEventListener('click', function (e) { e.preventDefault(); openPopup(); });
        }
        if (closeBtn) {
            closeBtn.addEventListener('click', function (e) { e.preventDefault(); closePopup(); });
        }
        if (overlay) {
            overlay.addEventListener('click', closePopup);
        }
        /* External openers: any [data-asp-open] element (e.g. a storefront's own
           mobile search button, wired via a per-theme OCMOD) opens this popup.
           Also exposed as window.aspOpenSearch(event) for inline onclick="". */
        var aspOpen = function (ev) {
            if (ev && ev.preventDefault) { ev.preventDefault(); }
            if (ev && ev.stopPropagation) { ev.stopPropagation(); }
            if (inlineMode) { try { input.focus(); } catch (e) {} } else { openPopup(); }
        };
        window.aspOpenSearch = aspOpen;
        var aspExtOpeners = document.querySelectorAll('[data-asp-open]');
        for (var aspI = 0; aspI < aspExtOpeners.length; aspI++) {
            aspExtOpeners[aspI].addEventListener('click', aspOpen);
        }
        /* Mobile: theme's #search-toggle reveals the search slot. */
        if (toggleBtn && blockEl) {
            toggleBtn.addEventListener('click', function () {
                blockEl.classList.add('active');
                if (inlineMode) { try { input.focus(); } catch (e) {} } else { openPopup(); }
            });
        }

        /* Track pointer-down inside the popup: a clicked chip can detach itself
           (popularWrap is cleared synchronously) before the click bubbles here,
           so results.contains(target) would wrongly report "outside". */
        var pointerDownInside = false;
        results.addEventListener('mousedown', function () { pointerDownInside = true; });
        results.addEventListener('touchstart', function () { pointerDownInside = true; }, { passive: true });

        document.addEventListener('click', function (e) {
            var inside = pointerDownInside;
            pointerDownInside = false;
            if (!isOpen) return;
            if (inside) return;
            if (results.contains(e.target)) return;
            if (stub && stub.contains(e.target)) return;
            if (toggleBtn && toggleBtn.contains(e.target)) return;
            closePopup();
        });

        /* Keep the detached popup glued to the stub on scroll / resize (popup only;
           inline mode uses CSS-absolute positioning under the input). */
        if (!inlineMode) {
            var reposIfOpen = function () { if (isOpen) positionPopup(); };
            window.addEventListener('resize', reposIfOpen);
            window.addEventListener('scroll', reposIfOpen, true);
        }

        /* Record the product on view so it can resurface in "recently viewed". */
        captureViewed(container.dataset.aspCurrentProduct);
        syncClear();

        /* ── Voice search (Web Speech API) ── */
        (function () {
            var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            var micBtn = results.querySelector('[data-asp-mic]');
            if (!SpeechRecognition || !micBtn) return;

            micBtn.style.display = '';   /* show only when API is available */
            var rec = null;

            micBtn.addEventListener('click', function () {
                if (rec) { rec.stop(); return; }
                rec = new SpeechRecognition();
                rec.lang = document.documentElement.lang || navigator.language || 'uk';
                rec.interimResults = false;
                rec.maxAlternatives = 1;

                micBtn.classList.add('asp-mic-active');
                micBtn.title = i18n.voiceListen;

                rec.onresult = function (e) {
                    var transcript = e.results[0][0].transcript.trim();
                    if (transcript) {
                        input.value = transcript;
                        input.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                };
                rec.onerror = rec.onend = function () {
                    micBtn.classList.remove('asp-mic-active');
                    micBtn.title = '';
                    rec = null;
                };
                rec.start();
            });
        }());
    }

    /* Bootstrap — wait for DOM so the [data-asp] elements exist */
    function boot() {
        var nodes = document.querySelectorAll('[data-asp]');
        Array.prototype.forEach.call(nodes, init);
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
