/**
 * Advanced Search Pro — AJAX filter (search results page).
 *
 * Intercepts clicks on facet links / price form submit / sort dropdown /
 * pagination, fetches the same search URL in the background and swaps the
 * results area in place — no page reload.
 *
 * Activation: requires `<div data-asp-results-root data-asp-filter-mode="own">`
 * wrapping the results section in the theme. Modes:
 *   - own       — this script handles AJAX swap.
 *   - ocfilter  — yields to OCFilter (this script does nothing).
 *   - off       — plain links / full page reload (this script does nothing).
 *
 * @author    oc-kit.com
 * @copyright Copyright (c) 2024-2026 oc-kit.com. All rights reserved.
 */
(function () {
    'use strict';

    function init() {
        var root = document.querySelector('[data-asp-results-root]');
        if (!root) {
            return; // not a search results page
        }
        if (root.dataset.aspFilterMode !== 'own') {
            return; // ocfilter / off — let the page behave normally
        }

        // Delegated handlers — work even after we swap innerHTML.
        document.addEventListener('click', onClick, true);
        document.addEventListener('submit', onSubmit, true);
        document.addEventListener('change', onChange, true);
        window.addEventListener('popstate', function () { swap(window.location.href, false); });
    }

    function onClick(e) {
        var root = document.querySelector('[data-asp-results-root]');
        if (!root || root.dataset.aspFilterMode !== 'own') return;

        var link = e.target.closest(
            '[data-asp-results-root] .asp-facets a, ' +
            '[data-asp-results-root] .page-pagination a, ' +
            '[data-asp-results-root] .pagination a'
        );
        if (!link) return;
        var href = link.getAttribute('href');
        if (!href || href === '#' || href.indexOf('javascript:') === 0) return;
        // Don't hijack target=_blank / ctrl+click / middle-click.
        if (link.target === '_blank' || e.metaKey || e.ctrlKey || e.button === 1) return;
        e.preventDefault();
        swap(href, true);
    }

    function onSubmit(e) {
        var root = document.querySelector('[data-asp-results-root]');
        if (!root || root.dataset.aspFilterMode !== 'own') return;

        var form = e.target.closest('[data-asp-results-root] .asp-facets form');
        if (!form) return;
        e.preventDefault();

        var fd = new FormData(form);
        var qs = new URLSearchParams();
        fd.forEach(function (v, k) {
            if (v !== '' && v !== null) qs.append(k, v);
        });
        var action = form.getAttribute('action') || window.location.pathname;
        var sep = action.indexOf('?') === -1 ? '?' : '&';
        swap(action + sep + qs.toString(), true);
    }

    function onChange(e) {
        var root = document.querySelector('[data-asp-results-root]');
        if (!root || root.dataset.aspFilterMode !== 'own') return;
        // Sort dropdown — uses onchange="location=this.value" in stock theme.
        if (e.target && e.target.id === 'input-sort' && e.target.value) {
            e.preventDefault();
            // Stop the inline onchange from also navigating.
            e.target.onchange = null;
            swap(e.target.value, true);
        }
    }

    var inflight = null;

    function swap(url, push) {
        var root = document.querySelector('[data-asp-results-root]');
        if (!root) {
            window.location.href = url;
            return;
        }
        if (inflight) {
            try { inflight.abort(); } catch (_) { }
        }
        root.classList.add('asp-ajax-loading');

        inflight = new AbortController();
        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
            credentials: 'same-origin',
            signal: inflight.signal
        })
            .then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.text();
            })
            .then(function (html) {
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var newRoot = doc.querySelector('[data-asp-results-root]');
                if (newRoot) {
                    root.innerHTML = newRoot.innerHTML;
                    if (push) {
                        try { history.pushState({ aspUrl: url }, '', url); } catch (_) { }
                    }
                    // Re-init lazy icons / scripts that look for fresh DOM.
                    if (window.lucide && typeof window.lucide.createIcons === 'function') {
                        try { window.lucide.createIcons(); } catch (_) { }
                    }
                    // Theme-specific re-binders (lazy-load images etc.) — fire a
                    // generic event so the theme can re-init its own widgets.
                    document.dispatchEvent(new CustomEvent('asp:results-updated', { detail: { url: url } }));
                    // Scroll up so user sees the new top of results.
                    var top = root.getBoundingClientRect().top + window.pageYOffset - 80;
                    window.scrollTo({ top: top, behavior: 'smooth' });
                } else {
                    // Marker missing in response — fall back to a normal load.
                    window.location.href = url;
                    return;
                }
            })
            .catch(function (err) {
                if (err && err.name === 'AbortError') return;
                // Network or parse error → don't trap the user, just go to the URL.
                window.location.href = url;
            })
            .then(function () {
                root.classList.remove('asp-ajax-loading');
                inflight = null;
            });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
