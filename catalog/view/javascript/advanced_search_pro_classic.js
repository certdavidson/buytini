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

    /* ------------------------------------------------------------------ */
    /* Widget initialiser                                                   */
    /* ------------------------------------------------------------------ */
    function init(container) {
        var input    = container.querySelector('[data-asp-input]');
        var results  = container.querySelector('[data-asp-results]');
        var list     = container.querySelector('[data-asp-list]');
        var empty    = container.querySelector('[data-asp-empty]');
        var footer   = container.querySelector('[data-asp-footer]');
        var viewAll  = container.querySelector('[data-asp-view-all]');
        var loading  = container.querySelector('[data-asp-loading]');
        var histWrap      = container.querySelector('[data-asp-history]');
        var popularWrap   = container.querySelector('[data-asp-popular-tags-wrap]');
        var facetsWrap    = container.querySelector('[data-asp-facets]');
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
            modelLabel:   container.dataset.aspI18nModel       || 'SKU'
        };

        var popularTags = (function () {
            try {
                var b64 = container.dataset.aspPopularTagsB64;
                if (b64) {
                    var parsed = JSON.parse(atob(b64));
                    if (Array.isArray(parsed)) return parsed;
                }
            } catch (e) {}
            return [];
        }());

        var timer          = null;
        var lastController = null;
        var selectedIndex  = -1;

        var isOpen         = false;

        /* ── DOM helpers ── */
        function show(el) { if (el) el.style.display = ''; }
        function hide(el) { if (el) el.style.display = 'none'; }

        function openResults() {
            results.classList.add('open');
            isOpen = true;
        }

        function closeResults() {
            results.classList.remove('open');
            isOpen = false;
            selectedIndex = -1;
            clearSelected();
            if (facetsWrap) { facetsWrap.innerHTML = ''; hide(facetsWrap); }
            if (popularWrap) { popularWrap.innerHTML = ''; hide(popularWrap); }
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

        function clearSelected() {
            var nodes = list.querySelectorAll('.asp-item.asp-selected');
            for (var i = 0; i < nodes.length; i++) {
                nodes[i].classList.remove('asp-selected');
                nodes[i].setAttribute('aria-selected', 'false');
            }
        }

        function setSelected(idx) {
            clearSelected();
            selectedIndex = idx;
            var items = list.querySelectorAll('.asp-item');
            if (idx >= 0 && idx < items.length) {
                items[idx].classList.add('asp-selected');
                items[idx].setAttribute('aria-selected', 'true');
                items[idx].scrollIntoView({ block: 'nearest' });
            }
        }

        /* ── History panel ── */
        function renderHistory() {
            if (!histWrap) return;
            var items = historyLoad();
            if (!items.length) {
                hide(histWrap);
                return;
            }
            histWrap.innerHTML = '';

            var header = document.createElement('div');
            header.className = 'asp-section-header';

            var label = document.createElement('span');
            label.className = 'asp-section-label';
            label.textContent = i18n.recent;
            header.appendChild(label);

            var clearBtn = document.createElement('button');
            clearBtn.type = 'button';
            clearBtn.className = 'asp-clear-history';
            clearBtn.textContent = i18n.clear;
            clearBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                historyClear();
                hide(histWrap);
                // If nothing else is visible in the dropdown — close it
                var hasItems = list && list.children.length > 0;
                if (!hasItems) {
                    closeResults();
                    input.blur();
                }
            });
            header.appendChild(clearBtn);
            histWrap.appendChild(header);

            var ul = document.createElement('div');
            ul.className = 'asp-history-list';
            items.slice(0, HISTORY_MAX).forEach(function (q) {
                var row = document.createElement('button');
                row.type = 'button';
                row.className = 'asp-history-item';
                row.textContent = q;
                row.addEventListener('click', function () {
                    input.value = q;
                    fetchItems(q);
                });
                ul.appendChild(row);
            });
            histWrap.appendChild(ul);
            show(histWrap);
        }

        /* ── Popular tags panel ── */
        function renderPopularTags() {
            if (!popularWrap || !popularTags.length) return;
            popularWrap.innerHTML = '';

            /* Determine page language: uk / ru / en */
            var langRaw = (document.documentElement.lang || '').toLowerCase();
            var lang = langRaw.indexOf('ru') === 0 ? 'ru' : langRaw.indexOf('en') === 0 ? 'en' : 'uk';

            var header = document.createElement('div');
            header.className = 'asp-section-header';
            var label = document.createElement('span');
            label.className = 'asp-section-label';
            label.textContent = i18n.popularTags;
            header.appendChild(label);
            popularWrap.appendChild(header);

            var tagList = document.createElement('div');
            tagList.className = 'asp-popular-tags-list';

            popularTags.forEach(function (tag) {
                /* Support plain string (auto mode) and object {names, urls} (manual mode) */
                var name, url;
                if (typeof tag === 'string') {
                    name = tag;
                    url  = '';
                } else {
                    var names = tag.names || {};
                    var urls  = tag.urls  || {};
                    name = names[lang] || names.uk || names.ru || names.en || '';
                    url  = urls[lang]  || '';
                }
                if (!name) return;

                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'asp-popular-tag';
                btn.textContent = name;
                btn.addEventListener('click', function () {
                    if (url) {
                        window.location.href = url;
                    } else {
                        input.value = name;
                        fetchItems(name);
                    }
                });
                tagList.appendChild(btn);
            });

            popularWrap.appendChild(tagList);
            show(popularWrap);
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
            selectedIndex = -1;
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

            items.forEach(function (item, idx) {
                var row = document.createElement('a');
                row.className = 'asp-item';
                row.href = (item.href || '#').replace(/&amp;/g, '&');
                row.setAttribute('role', 'option');
                row.dataset.idx = idx;

                if (item.image) {
                    var img = document.createElement('img');
                    img.className = 'asp-img';
                    img.src = item.image;
                    img.alt = '';
                    img.loading = 'lazy';
                    row.appendChild(img);
                }

                var body = document.createElement('div');
                body.className = 'asp-body';

                var titleEl = document.createElement('div');
                titleEl.className = 'asp-title';
                if (item.name_hl) {
                    titleEl.innerHTML = item.name_hl; /* server-escaped + <mark> only */
                } else {
                    titleEl.textContent = item.name;
                }
                body.appendChild(titleEl);

                if (item.product_id) {
                    var metaEl = document.createElement('div');
                    metaEl.className = 'asp-meta';

                    var idEl = document.createElement('span');
                    idEl.className = 'asp-meta-id';
                    idEl.textContent = i18n.idLabel + ': ' + item.product_id;
                    metaEl.appendChild(idEl);

                    if (item.model) {
                        var modelEl = document.createElement('span');
                        modelEl.className = 'asp-meta-model';
                        modelEl.textContent = i18n.modelLabel + ': ' + item.model;
                        metaEl.appendChild(modelEl);
                    }

                    body.appendChild(metaEl);
                }

                if (item.type === 'category') {
                    body.appendChild(makeBadge(i18n.category, 'asp-badge-cat'));
                } else if (item.type === 'brand') {
                    body.appendChild(makeBadge(i18n.brand, 'asp-badge-brand'));
                } else if (item.type === 'query') {
                    body.appendChild(makeBadge(i18n.popular, 'asp-badge-pop'));
                }

                if (item.price) {
                    var priceEl = document.createElement('div');
                    priceEl.className = 'asp-price';

                    if (item.special) {
                        var saleBadge = document.createElement('span');
                        saleBadge.className = 'asp-sale-badge';
                        saleBadge.textContent = item.discount_pct > 0 ? '-' + item.discount_pct + '%' : i18n.sale;
                        priceEl.appendChild(saleBadge);

                        var specialEl = document.createElement('span');
                        specialEl.className = 'asp-price-special';
                        specialEl.textContent = item.special;
                        priceEl.appendChild(specialEl);

                        var oldEl = document.createElement('span');
                        oldEl.className = 'asp-price-old';
                        oldEl.textContent = item.price;
                        priceEl.appendChild(oldEl);
                    } else {
                        priceEl.textContent = item.price;
                    }

                    body.appendChild(priceEl);
                }

                if (typeof item.in_stock !== 'undefined') {
                    var stockEl = document.createElement('div');
                    stockEl.className = item.in_stock ? 'asp-stock asp-in-stock' : 'asp-stock asp-out-of-stock';
                    stockEl.textContent = item.in_stock ? i18n.inStock : (item.stock_status || i18n.outOfStock);
                    body.appendChild(stockEl);
                }

                row.appendChild(body);

                row.addEventListener('mouseenter', function () { setSelected(idx); });

                list.appendChild(row);
            });
        }

        function makeBadge(text, cls) {
            var el = document.createElement('span');
            el.className = 'asp-badge ' + cls;
            el.textContent = text;
            return el;
        }

        /* ── Fetch ── */
        function fetchItems(q) {
            if (lastController) lastController.abort();
            lastController = new AbortController();

            setLoading(true);
            hide(empty);
            hide(histWrap);
            if (popularWrap) { popularWrap.innerHTML = ''; hide(popularWrap); }

            fetch(endpoint + '&q=' + encodeURIComponent(q), { signal: lastController.signal })
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
            hide(empty);
            list.innerHTML = '';
            if (footer) hide(footer);
            renderHistory();
            renderPopularTags();
            openResults();
        }

        /* ── Keyboard navigation ── */
        input.addEventListener('keydown', function (e) {
            if (!isOpen) return;
            var items = list.querySelectorAll('.asp-item');
            var count = items.length;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                setSelected(selectedIndex < count - 1 ? selectedIndex + 1 : 0);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                setSelected(selectedIndex > 0 ? selectedIndex - 1 : count - 1);
            } else if (e.key === 'Enter') {
                if (selectedIndex >= 0 && selectedIndex < count) {
                    e.preventDefault();
                    var href = items[selectedIndex].href;
                    if (href && href !== '#') {
                        historyAdd(input.value.trim());
                        window.location.href = href;
                    }
                } else {
                    var q = input.value.trim();
                    if (q) {
                        historyAdd(q);
                        if (viewAll && viewAll.href && viewAll.href !== '#') {
                            window.location.href = viewAll.href;
                        }
                    }
                }
            } else if (e.key === 'Escape') {
                closeResults();
                input.blur();
            }
        });

        /* ── Input events ── */
        input.addEventListener('input', function () {
            var q = input.value.trim();
            clearTimeout(timer);
            if (q.length < minChars) {
                if (q.length === 0) { showHistory(); } else { closeResults(); }
                return;
            }
            timer = setTimeout(function () { fetchItems(q); }, delay);
        });

        input.addEventListener('focus', function () {
            var q = input.value.trim();
            if (q.length >= minChars) { fetchItems(q); } else { showHistory(); }
        });

        input.addEventListener('blur', function () {
            var q = input.value.trim();
            if (q.length >= minChars) historyAdd(q);
        });

        var submitBtn = container.querySelector('[data-asp-submit]');
        if (submitBtn) {
            submitBtn.addEventListener('click', function () {
                var q = input.value.trim();
                if (!q) return;
                historyAdd(q);
                if (viewAll && viewAll.href && viewAll.href !== '#') {
                    window.location.href = viewAll.href;
                }
            });
        }

        document.addEventListener('click', function (e) {
            if (!container.contains(e.target)) closeResults();
        });

        /* ── Voice search (Web Speech API) ── */
        (function () {
            var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            var micBtn = container.querySelector('[data-asp-mic]');
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
