/**
 * Translater Pro — Admin JS
 * © 2026 oc-kit.com | https://oc-kit.com
 *
 * Vanilla JS IIFE, no jQuery dependency.
 * Texts from window.tpI18n, endpoints from window.tpUrls, config from window.tpConfig.
 */
(function () {
    'use strict';

    const I18n   = window.tpI18n   || {};
    const Urls   = window.tpUrls   || {};
    const Config = window.tpConfig || {};

    // ─── Tab switching ────────────────────────────────────────────────────────

    document.querySelectorAll('.ok-tabs-sidebar-item[data-tab]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.ok-tabs-sidebar-item').forEach(function (b) {
                b.classList.remove('active');
            });
            document.querySelectorAll('.ok-layout-panel').forEach(function (p) {
                p.classList.remove('active');
            });
            btn.classList.add('active');
            const panel = document.getElementById(btn.dataset.tab);
            if (panel) panel.classList.add('active');

            if (btn.dataset.tab === 'tab-translate') {
                // Auto-load items when switching to translate tab
                const srcLang = document.getElementById('tr-source-lang')?.value || '';
                const tgtLang = document.getElementById('tr-target-lang')?.value || '';
                if (srcLang && tgtLang && srcLang !== tgtLang) {
                    loadItems(1);
                }
            }
            if (btn.dataset.tab === 'tab-logs' && !logsLoaded) {
                loadLogs(1);
            }
            if (btn.dataset.tab === 'tab-settings') {
                updateCronCommands();
            }
        });
    });

    // ─── Save settings ────────────────────────────────────────────────────────

    document.getElementById('btn-save')?.addEventListener('click', function () {
        const form = document.getElementById('form-tp');
        const data = new FormData(form);

        fetch(form.action, { method: 'POST', body: data })
            .then(function (r) { return r.json(); })
            .then(function (json) {
                if (json.success) {
                    window.okNotify(json.success, 'success');
                    updateCronCommands();
                } else {
                    window.okNotify(json.error || I18n.text_error, 'error');
                }
            })
            .catch(function (e) {
                window.okNotify(e.message, 'error');
            });
    });

    // ─── Reset prompt to default ──────────────────────────────────────────────

    document.getElementById('btn-reset-prompt')?.addEventListener('click', function () {
        const ta = document.getElementById('prompt-textarea');
        if (ta && Config.defaultPrompt) {
            ta.value = Config.defaultPrompt;
        }
    });

    // ─── API provider section toggle ──────────────────────────────────────────

    const providerSelect = document.getElementById('api-provider');
    if (providerSelect) {
        providerSelect.addEventListener('change', showProviderSection);
        showProviderSection();
    }

    function showProviderSection() {
        const val = providerSelect ? providerSelect.value : 'openai';
        document.querySelectorAll('.ok-api-section').forEach(function (el) {
            el.classList.add('ok-hidden');
        });
        const target = document.getElementById('section-' + val);
        if (target) target.classList.remove('ok-hidden');
    }

    // ─── Dashboard: stats ─────────────────────────────────────────────────────

    document.getElementById('btn-refresh-stats')?.addEventListener('click', function () {
        loadStats();
    });

    function loadStats() {
        const srcLang = document.getElementById('dash-source-lang')?.value || '';
        const tgtLang = document.getElementById('dash-target-lang')?.value || '';

        if (!srcLang || !tgtLang || srcLang === tgtLang) return;

        const loading = document.getElementById('stats-loading');
        if (loading) loading.classList.remove('ok-hidden');

        fetch(Urls.stats + '&source_lang=' + encodeURIComponent(srcLang) + '&target_lang=' + encodeURIComponent(tgtLang))
            .then(function (r) { return r.json(); })
            .then(function (json) {
                if (loading) loading.classList.add('ok-hidden');
                if (json.error) {
                    window.okNotify(json.error, 'error');
                    return;
                }
                const stats = json.stats || {};
                Object.keys(stats).forEach(function (type) {
                    const valEl  = document.getElementById('stat-' + type);
                    const card   = document.getElementById('card-' + type);
                    if (!valEl) return;
                    const count = stats[type];
                    valEl.textContent = count;
                    if (card) {
                        card.classList.remove('ok-kpi-card--ok', 'ok-kpi-card--lost');
                        card.classList.add(count === 0 ? 'ok-kpi-card--ok' : 'ok-kpi-card--lost');
                    }
                });
            })
            .catch(function (e) {
                if (loading) loading.classList.add('ok-hidden');
                window.okNotify(e.message, 'error');
            });
    }

    // Auto-load stats on page open if langs are already selected
    (function () {
        const srcLang = document.getElementById('dash-source-lang')?.value || '';
        const tgtLang = document.getElementById('dash-target-lang')?.value || '';
        if (srcLang && tgtLang && srcLang !== tgtLang) {
            loadStats();
        }
    })();

    // ─── Translate tab: items ─────────────────────────────────────────────────

    let currentPage  = 1;
    let totalPages   = 1;
    let totalItems   = 0;
    let allItemIds   = []; // ids of all items on current page
    let currentType  = 'product';
    let translateQueue = [];
    let translating    = false;

    document.getElementById('btn-load-items')?.addEventListener('click', function () {
        loadItems(1);
    });

    document.getElementById('tr-per-page')?.addEventListener('change', function () {
        loadItems(1);
    });

    document.getElementById('tr-select-all')?.addEventListener('change', function () {
        const checked = this.checked;
        document.querySelectorAll('input[name="tp-item"]').forEach(function (cb) {
            cb.checked = checked;
        });
        updateTranslateButtons();
    });

    document.addEventListener('change', function (e) {
        if (e.target.name === 'tp-item') {
            updateTranslateButtons();
        }
    });

    document.getElementById('btn-translate-selected')?.addEventListener('click', function () {
        const selected = getSelectedIds();
        if (!selected.length) {
            window.okNotify(I18n.text_select_items, 'warning');
            return;
        }
        startTranslation(selected);
    });

    document.getElementById('btn-translate-all')?.addEventListener('click', function () {
        startTranslation(allItemIds);
    });

    // Pagination delegation
    document.getElementById('tr-pagination')?.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-page]');
        if (!btn || translating) return;
        loadItems(parseInt(btn.dataset.page));
    });

    document.getElementById('logs-pagination')?.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-page]');
        if (!btn) return;
        loadLogs(parseInt(btn.dataset.page));
    });

    function loadItems(page) {
        currentPage = page;
        const srcLang = document.getElementById('tr-source-lang')?.value || '';
        const tgtLang = document.getElementById('tr-target-lang')?.value || '';
        currentType   = document.getElementById('tr-type')?.value || 'product';

        const tbody = document.getElementById('tr-tbody');
        tbody.innerHTML = '<tr><td colspan="6" class="text-center" style="color:#999;padding:20px;">' + I18n.text_loading + '</td></tr>';

        document.getElementById('btn-translate-selected').disabled = true;
        document.getElementById('btn-translate-all').disabled      = true;

        const overwrite = document.getElementById('tr-overwrite')?.checked ? '1' : '0';
        const perPage   = document.getElementById('tr-per-page')?.value || '30';

        const url = Urls.items + '&type=' + encodeURIComponent(currentType)
            + '&source_lang=' + encodeURIComponent(srcLang)
            + '&target_lang=' + encodeURIComponent(tgtLang)
            + '&page=' + page
            + '&overwrite=' + overwrite
            + '&limit=' + perPage;

        fetch(url)
            .then(function (r) { return r.json(); })
            .then(function (json) {
                if (json.error) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center" style="color:#dc3545;padding:20px;">' + escHtml(json.error) + '</td></tr>';
                    return;
                }

                totalPages = json.pages || 1;
                totalItems = json.total || 0;
                allItemIds = (json.items || []).map(function (it) { return it.item_id; });

                document.getElementById('tr-total-label').textContent =
                    I18n.text_total + ': ' + totalItems;

                if (!json.items || !json.items.length) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center" style="color:#999;padding:20px;">' + I18n.text_no_results + '</td></tr>';
                    renderPagination('tr-pagination', page, totalPages);
                    return;
                }

                renderItemRows(json.items, currentType);
                renderPagination('tr-pagination', page, totalPages);

                document.getElementById('btn-translate-selected').disabled = false;
                document.getElementById('btn-translate-all').disabled      = false;
            })
            .catch(function (e) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center" style="color:#dc3545;padding:20px;">' + escHtml(e.message) + '</td></tr>';
            });
    }

    function renderItemRows(items, type) {
        const tbody = document.getElementById('tr-tbody');
        tbody.innerHTML = '';

        const adminUrls   = Config.adminEditUrls      || {};
        const catalogUrls = Config.catalogUrlPatterns || {};

        items.forEach(function (item) {
            const id          = item.item_id;
            const adminBase   = adminUrls[type]   || '';
            const catalogBase = catalogUrls[type] || '';

            const idCell = adminBase
                ? '<a href="' + escHtml(adminBase + id) + '" target="_blank" title="' + escHtml(I18n.column_id || 'Edit') + '">' + id + '</a>'
                  + (catalogBase
                    ? ' <a class="ok-copy-btn" href="' + escHtml(catalogBase + id) + '" target="_blank" title="View in catalog" style="color:#888;margin-left:4px;"><i data-lucide="external-link"></i></a>'
                    : '')
                : String(id);

            const tr = document.createElement('tr');
            tr.dataset.itemId = id;
            tr.innerHTML =
                '<td><input type="checkbox" name="tp-item" value="' + id + '"></td>' +
                '<td style="white-space:nowrap;">' + idCell + '</td>' +
                '<td>' + escHtml(item.display_name || '—') + '</td>' +
                '<td style="font-size:11px;color:#888;">' + escHtml((item.fields || []).join(', ')) + '</td>' +
                '<td style="font-size:12px;color:#555;">' + escHtml(item.preview || '') + '</td>' +
                '<td><span class="tp-status-pending">—</span></td>';
            tbody.appendChild(tr);
        });

        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function getSelectedIds() {
        return Array.from(document.querySelectorAll('input[name="tp-item"]:checked'))
            .map(function (cb) { return parseInt(cb.value); });
    }

    function updateTranslateButtons() {
        const hasSelected = getSelectedIds().length > 0;
        const btn = document.getElementById('btn-translate-selected');
        if (btn) btn.disabled = !hasSelected;
    }

    // ─── Translation process ──────────────────────────────────────────────────

    function startTranslation(ids) {
        if (translating || !ids.length) return;
        translating    = true;
        translateQueue = ids.slice();

        const srcLang = document.getElementById('tr-source-lang')?.value || '';
        const tgtLang = document.getElementById('tr-target-lang')?.value || '';
        const type    = document.getElementById('tr-type')?.value || 'product';

        const total = translateQueue.length;
        let done    = 0;

        showProgress(0, total);
        disableTranslateButtons(true);

        function next() {
            if (!translateQueue.length) {
                translating = false;
                hideProgress();
                disableTranslateButtons(false);
                window.okNotify(I18n.text_all_translated, 'success');
                // Reload to show remaining items
                setTimeout(function () { loadItems(currentPage); }, 600);
                return;
            }

            const itemId = translateQueue.shift();
            done++;
            updateProgress(done, total, I18n.text_translating + ' ' + done + '/' + total);
            setRowStatus(itemId, 'translating', I18n.text_translating);

            fetch(Urls.translate, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({
                    type:        type,
                    item_id:     itemId,
                    source_lang: srcLang,
                    target_lang: tgtLang
                })
            })
                .then(function (r) { return r.json(); })
                .then(function (json) {
                    if (json.success) {
                        setRowStatus(itemId, 'done', I18n.text_done);
                    } else {
                        setRowStatus(itemId, 'error', json.error || I18n.text_error);
                    }
                    next();
                })
                .catch(function (e) {
                    setRowStatus(itemId, 'error', e.message);
                    next();
                });
        }

        next();
    }

    function setRowStatus(itemId, state, text) {
        const tr = document.querySelector('tr[data-item-id="' + itemId + '"]');
        if (!tr) return;
        const td = tr.querySelector('td:last-child');
        if (!td) return;
        const cls = state === 'done' ? 'tp-status-done' : state === 'error' ? 'tp-status-error' : 'tp-status-pending';
        td.innerHTML = '<span class="' + cls + '">' + escHtml(text) + '</span>';

        if (state === 'done') {
            tr.style.opacity = '0.5';
        }
    }

    function showProgress(done, total) {
        const wrap = document.getElementById('tr-progress-wrap');
        if (wrap) wrap.classList.remove('ok-hidden');
        updateProgress(done, total, '');
    }

    function hideProgress() {
        const wrap = document.getElementById('tr-progress-wrap');
        if (wrap) setTimeout(function () { wrap.classList.add('ok-hidden'); }, 1500);
    }

    function updateProgress(done, total, label) {
        const pct     = total > 0 ? Math.round(done / total * 100) : 0;
        const bar     = document.getElementById('tr-progress-bar');
        const pctEl   = document.getElementById('tr-progress-pct');
        const labelEl = document.getElementById('tr-progress-label');
        if (bar)     bar.style.width     = pct + '%';
        if (pctEl)   pctEl.textContent   = pct + '%';
        if (labelEl) labelEl.textContent = label;
    }

    function disableTranslateButtons(disabled) {
        ['btn-translate-selected', 'btn-translate-all', 'btn-load-items'].forEach(function (id) {
            const el = document.getElementById(id);
            if (el) el.disabled = disabled;
        });
    }

    // ─── Pagination renderer ──────────────────────────────────────────────────

    function renderPagination(containerId, page, pages) {
        const el = document.getElementById(containerId);
        if (!el) return;
        el.innerHTML = '';
        if (pages <= 1) return;

        if (page > 1) {
            el.appendChild(makePageBtn(I18n.button_prev_page || '«', page - 1));
        }
        el.appendChild(document.createTextNode(
            ' ' + (I18n.text_page || 'Page') + ' ' + page + ' ' + (I18n.text_of || 'of') + ' ' + pages + ' '
        ));
        if (page < pages) {
            el.appendChild(makePageBtn(I18n.button_next_page || '»', page + 1));
        }
    }

    function makePageBtn(label, page) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'ok-btn ok-btn-default ok-btn-sm';
        btn.textContent = label;
        btn.dataset.page = page;
        return btn;
    }

    // ─── Logs ─────────────────────────────────────────────────────────────────

    let logsLoaded  = false;
    let logsPage    = 1;
    let logsStatus  = '';

    document.querySelectorAll('.ok-log-filter').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.ok-log-filter').forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            logsStatus = btn.dataset.status;
            loadLogs(1);
        });
    });

    function loadLogs(page) {
        logsLoaded = true;
        logsPage   = page;
        const tbody = document.getElementById('logs-tbody');
        tbody.innerHTML = '<tr><td colspan="10" class="text-center" style="color:#999;padding:20px;">' + I18n.text_loading + '</td></tr>';

        const statusParam = logsStatus ? '&status=' + encodeURIComponent(logsStatus) : '';
        fetch(Urls.logs + '&page=' + page + statusParam)
            .then(function (r) { return r.json(); })
            .then(function (json) {
                if (json.error) {
                    tbody.innerHTML = '<tr><td colspan="10" class="text-center" style="color:#dc3545;padding:20px;">' + escHtml(json.error) + '</td></tr>';
                    return;
                }
                if (!json.logs || !json.logs.length) {
                    tbody.innerHTML = '<tr><td colspan="10" class="text-center" style="color:#999;padding:20px;">' + (I18n.text_no_logs || '—') + '</td></tr>';
                    return;
                }
                tbody.innerHTML = '';
                json.logs.forEach(function (log) {
                    const isErr   = log.status === 'error';
                    const badge   = isErr
                        ? '<span class="ok-badge ok-badge-danger">error</span>'
                        : '<span class="ok-badge ok-badge-success">ok</span>';
                    const errCell = isErr
                        ? '<td style="font-size:12px;color:#dc3545;word-break:break-all;">' + escHtml(log.error_msg) + '</td>'
                        : '<td style="color:#999;">—</td>';
                    const tr = document.createElement('tr');
                    tr.innerHTML =
                        '<td>' + log.log_id + '</td>' +
                        '<td>' + badge + '</td>' +
                        '<td>' + escHtml(log.type) + '</td>' +
                        '<td>' + log.item_id + '</td>' +
                        '<td>' + escHtml(log.source_lang) + '</td>' +
                        '<td>' + escHtml(log.target_lang) + '</td>' +
                        '<td>' + escHtml(log.provider) + '</td>' +
                        '<td style="text-align:center;">' + (log.fields_count || '—') + '</td>' +
                        errCell +
                        '<td style="font-size:12px;">' + escHtml(log.date_added) + '</td>';
                    tbody.appendChild(tr);
                });
                renderPagination('logs-pagination', page, json.pages || 1);
            })
            .catch(function (e) {
                tbody.innerHTML = '<tr><td colspan="10" class="text-center" style="color:#dc3545;padding:20px;">' + escHtml(e.message) + '</td></tr>';
            });
    }

    document.getElementById('btn-clear-logs')?.addEventListener('click', function () {
        if (!confirm(I18n.text_confirm_clear_logs)) return;

        fetch(Urls.clearLogs, { method: 'POST' })
            .then(function (r) { return r.json(); })
            .then(function (json) {
                if (json.success) {
                    window.okNotify(I18n.text_done, 'success');
                    loadLogs(1);
                } else {
                    window.okNotify(json.error || I18n.text_error, 'error');
                }
            });
    });

    // ─── Cron command generator ───────────────────────────────────────────────

    function updateCronCommands() {
        const wrap = document.getElementById('cron-commands-wrap');
        if (!wrap) return;

        const cronPhp = Config.cronPhpPath || '';
        const cronLog = Config.cronLogPath || '';

        const source    = document.getElementById('cron-source-lang')?.value || '';
        const typesEl   = document.getElementById('cron-types');
        const targetsEl = document.getElementById('cron-target-langs');
        const types   = typesEl   ? Array.from(typesEl.options).filter(function(o){ return o.selected; }).map(function(o){ return o.value; }) : [];
        const targets = targetsEl ? Array.from(targetsEl.options).filter(function(o){ return o.selected; }).map(function(o){ return o.value; }) : [];
        const batch   = document.getElementById('cron-batch')?.value || '20';

        if (!source || !targets.length || !types.length) {
            wrap.innerHTML = '<p class="ok-help" style="margin:0;color:#aaa;">Оберіть мову оригіналу, цільові мови та типи — команди з\'являться тут.</p>';
            return;
        }

        const typesStr = types.join(',');
        const lines    = targets
            .filter(function (t) { return t !== source; })
            .map(function (target) {
                const cmd  = 'php ' + cronPhp +
                    ' --source=' + source +
                    ' --target=' + target +
                    ' --types=' + typesStr +
                    ' --batch=' + batch;
                const cron = '0 2 * * * ' + cmd + (cronLog ? ' >> ' + cronLog + ' 2>&1' : '');
                return '<div style="margin-bottom:6px;">' +
                    '<div style="font-size:11px;color:#888;margin-bottom:2px;">' + escHtml(source) + ' → ' + escHtml(target) + '</div>' +
                    '<code style="display:block;background:#f5f5f5;padding:6px 10px;border-radius:6px;font-size:11px;word-break:break-all;user-select:all;">' +
                    escHtml(cron) + '</code></div>';
            });

        if (!lines.length) {
            wrap.innerHTML = '<p class="ok-help" style="margin:0;color:#aaa;">Цільові мови збігаються з мовою оригіналу.</p>';
            return;
        }

        wrap.innerHTML = lines.join('');
    }

    // Update on change of any cron setting
    ['cron-source-lang', 'cron-target-langs', 'cron-types', 'cron-batch'].forEach(function (id) {
        document.getElementById(id)?.addEventListener('change', updateCronCommands);
        document.getElementById(id)?.addEventListener('input', updateCronCommands);
    });

    // Initial render
    updateCronCommands();

    // Render lucide icons in the static markup
    if (typeof lucide !== 'undefined') lucide.createIcons();

    // ─── Util ─────────────────────────────────────────────────────────────────

    function escHtml(str) {
        const d = document.createElement('div');
        d.appendChild(document.createTextNode(String(str || '')));
        return d.innerHTML;
    }

})();
