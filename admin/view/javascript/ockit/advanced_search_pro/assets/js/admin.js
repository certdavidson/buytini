// Advanced Search Pro — Admin JS | © 2024-2026 oc-kit.com | https://oc-kit.com
(function () {
    'use strict';

    // Texts come from aspI18n (set inline in the template via Twig language vars).
    // URLs come from data-url attributes on the relevant elements.
    // AI model list comes from aspModelOpts.
    var L = typeof aspI18n !== 'undefined' ? aspI18n : {};

    // ─── Tabs ────────────────────────────────────────────────────────────────────
    if (typeof window.initTabs === 'function') {
        window.initTabs('.ok-tabs-sidebar', '.ok-tabs-sidebar-item', '.ok-layout-panel');
    }

    // ─── Lucide icons ────────────────────────────────────────────────────────────
    if (window.lucide) {
        lucide.createIcons();
        // Auto-render any <i data-lucide> added later by dynamic UI (synonym rows,
        // attribute search results, query-rules table, etc.).
        // IMPORTANT: filter for <i data-lucide> placeholders only. Lucide replaces
        // them with <svg data-lucide="..."> — observing every DOM mutation (or
        // matching by attribute alone) would re-process the replacement output
        // forever and flood the page with MutationRecord events.
        if ('MutationObserver' in window) {
            var lucideTick = null;
            new MutationObserver(function (mutations) {
                var found = false;
                for (var i = 0; i < mutations.length && !found; i++) {
                    var added = mutations[i].addedNodes;
                    for (var j = 0; j < added.length; j++) {
                        var n = added[j];
                        if (n.nodeType !== 1) continue;
                        if (n.tagName === 'I' && n.hasAttribute('data-lucide')) { found = true; break; }
                        if (n.querySelector && n.querySelector('i[data-lucide]')) { found = true; break; }
                    }
                }
                if (!found || lucideTick) return;
                lucideTick = setTimeout(function () { lucide.createIcons(); lucideTick = null; }, 50);
            }).observe(document.body, { childList: true, subtree: true });
        }
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────────
    function notify(msg, type) {
        if (typeof window.okNotify === 'function') window.okNotify(msg, type || 'success');
    }

    function postTo(url, body, onDone, onFail) {
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: new URLSearchParams(body)
        }).then(function (r) { return r.json(); }).then(onDone)
          .catch(function () { if (typeof onFail === 'function') onFail(); });
    }

    function getFrom(url, onDone, onFail) {
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); }).then(onDone)
            .catch(function () { if (typeof onFail === 'function') onFail(); });
    }

    function logLine(el, text) {
        if (!el) return;
        el.value = new Date().toISOString().replace('T', ' ').slice(0, 19) + ' ' + text + '\n' + el.value;
    }

    function esc(s) {
        return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function $(id) { return document.getElementById(id); }
    function url(el) { return el ? el.dataset.url : ''; }

    function syncEffBar(card, isSelected) {
        var bar = card.querySelector('.ok-quiz-card-lg-eff-bar');
        if (!bar) return;
        bar.classList.toggle('ok-progress-bar-success', !!isSelected);
    }

    // ─── Search fields: weight range inputs ──────────────────────────────────────
    document.addEventListener('input', function (e) {
        if (!e.target || !e.target.classList.contains('asp-field-weight-range')) return;
        var key = e.target.dataset.target;
        var val = e.target.value;
        var label = document.getElementById(key);
        var hidden = document.getElementById(key.replace('asp-fw-', 'asp-fw-h-'));
        if (label) label.textContent = val;
        if (hidden) hidden.value = val;
    });

    // ─── Filters: toggle sub-options visibility ───────────────────────────────────
    var filterEnabledInput = document.querySelector('input[name="module_oc_kit_advanced_search_pro_filter_enabled"][type="checkbox"]');
    var filterOptions = $('asp-filter-options');
    if (filterEnabledInput && filterOptions) {
        filterEnabledInput.addEventListener('change', function () {
            filterOptions.style.display = this.checked ? '' : 'none';
        });
    }

    // ─── Hybrid ratio slider ─────────────────────────────────────────────────────
    var ratio = $('asp-vector-ratio'), ratioH = $('asp-vector-ratio-hidden'), ratioV = $('asp-vector-ratio-value');
    if (ratio && ratioH && ratioV) {
        ratio.addEventListener('input', function () { ratioH.value = ratioV.textContent = ratio.value; });
    }

    // ─── Wizard ──────────────────────────────────────────────────────────────────
    var wizAi        = $('wiz-ai');
    var wizAiRow     = $('wiz-ai-key-row');
    var wizMode      = $('wiz-mode');
    var wizConnBlock = $('wiz-conn-block');

    function wizUpdateMode() {
        if (!wizMode) return;
        if (wizConnBlock) wizConnBlock.style.display = wizMode.value === 'native' ? 'none' : '';
    }

    // Wizard mode card selection
    document.addEventListener('click', function (e) {
        var card = e.target.closest('.wiz-mode-card');
        if (!card) return;
        document.querySelectorAll('.wiz-mode-card').forEach(function (c) { c.classList.remove('selected'); syncEffBar(c, false); });
        card.classList.add('selected');
        syncEffBar(card, true);
        if (wizMode) { wizMode.value = card.dataset.value || 'native'; }
        wizUpdateMode();
    });

    // Generic settings card picker (asp-pick-card + data-input)
    document.addEventListener('click', function (e) {
        var card = e.target.closest('.asp-pick-card');
        if (!card) return;
        var inputId = card.dataset.input;
        if (!inputId) return;
        document.querySelectorAll('.asp-pick-card[data-input="' + inputId + '"]').forEach(function (c) { c.classList.remove('selected'); syncEffBar(c, false); });
        card.classList.add('selected');
        syncEffBar(card, true);
        var inp = $(inputId);
        if (inp) {
            inp.value = card.dataset.value || '';
            // Hidden inputs don't fire a native 'change' on .value assignment —
            // dispatch one so listeners (e.g. AI provider → model list rebuild)
            // get notified.
            inp.dispatchEvent(new Event('change', { bubbles: true }));
            updateModeBlocks(inp.value);
        }
    });

    // ─── Mode-dependent blocks (data-require-mode / data-show-mode) ─────────────
    function updateModeBlocks(currentMode) {
        // Blocks that require a specific mode — show alert + disable toggles when mismatched
        document.querySelectorAll('.asp-mode-block[data-require-mode]').forEach(function (block) {
            var allowed = (block.dataset.requireMode || '').split(',').map(function (s) { return s.trim(); });
            var ok = allowed.indexOf(currentMode) !== -1;
            var alert = block.querySelector('.asp-mode-alert');
            var toggles = block.querySelectorAll('.ok-toggle-input');
            if (alert) alert.style.display = ok ? 'none' : '';
            toggles.forEach(function (t) { t.disabled = !ok; });
        });
        // Elements that should only be visible in a specific mode
        document.querySelectorAll('[data-show-mode]').forEach(function (el) {
            var modes = (el.dataset.showMode || '').split(',').map(function (s) { return s.trim(); });
            el.style.display = modes.indexOf(currentMode) !== -1 ? '' : 'none';
        });
    }
    // Run on page load using current hidden input value
    var aspModeInput = $('asp-mode');
    if (aspModeInput) updateModeBlocks(aspModeInput.value);

    // Sync eff-bar success colour for all initially-selected quiz cards
    document.querySelectorAll('.ok-quiz-card-lg').forEach(function (c) {
        syncEffBar(c, c.classList.contains('selected'));
    });

    if (wizAi && wizAiRow) {
        wizAi.addEventListener('change', function () { wizAiRow.style.display = wizAi.checked ? '' : 'none'; });
    }
    if (wizMode) {
        wizUpdateMode();
    }

    var wizApplyBtn = $('asp-wiz-apply');
    var wizStatus   = $('asp-wiz-status');
    if (wizApplyBtn) {
        wizApplyBtn.addEventListener('click', function () {
            var wizUrl = url(wizApplyBtn);
            if (!wizUrl) return;
            wizApplyBtn.disabled = true;
            if (wizStatus) wizStatus.textContent = L.wizApplying || 'Зберігаємо…';
            postTo(wizUrl, {
                wiz_mode:         wizMode ? wizMode.value : 'native',
                wiz_host:         $('wiz-host')     ? $('wiz-host').value     : '',
                wiz_port:         $('wiz-port')     ? $('wiz-port').value     : '',
                wiz_index:        $('wiz-index')    ? $('wiz-index').value    : '',
                wiz_login:        $('wiz-login')    ? $('wiz-login').value    : '',
                wiz_password:     $('wiz-password') ? $('wiz-password').value : '',
                wiz_ai_key:       $('wiz-ai-key') ? $('wiz-ai-key').value : '',
                wiz_cron_enabled: $('wiz-cron') && $('wiz-cron').checked ? '1' : '0'
            }, function (data) {
                if (data.status === 'ok') {
                    if (wizStatus) wizStatus.textContent = L.wizApplied || 'Готово!';
                    setTimeout(function () { window.location.href = wizApplyBtn.dataset.redirect || window.location.href; }, 800);
                } else {
                    if (wizStatus) wizStatus.textContent = data.error || 'Помилка';
                    wizApplyBtn.disabled = false;
                }
            }, function () {
                if (wizStatus) wizStatus.textContent = L.requestFailed || 'Помилка';
                wizApplyBtn.disabled = false;
            });
        });
    }

    var wizResetBtn = $('asp-wiz-reset');
    if (wizResetBtn) {
        wizResetBtn.addEventListener('click', function () {
            var wizUrl = url(wizResetBtn);
            if (!wizUrl) return;
            postTo(wizUrl, { reset: '1' }, function () { window.location.reload(); });
        });
    }

    // ─── AI provider → model select ──────────────────────────────────────────────
    var aiProviderEl = $('asp-ai-provider');
    var aiModelEl    = $('asp-ai-model');
    var modelOpts    = typeof aspModelOpts !== 'undefined' ? aspModelOpts : {};

    function rebuildModels(provider, current) {
        if (!aiModelEl) return;
        var list = modelOpts[provider] || [];
        aiModelEl.innerHTML = '';
        list.forEach(function (m) {
            var o = document.createElement('option');
            o.value = o.textContent = m;
            if (m === current) o.selected = true;
            aiModelEl.appendChild(o);
        });
    }
    // Per-provider API key fields — each provider has its own input; show only
    // the one matching the currently selected provider. On save the controller
    // copies the active provider's value into the legacy `ai_api_key` setting
    // so the catalog code keeps reading a single key.
    function refreshApiKeyBlocks(provider) {
        document.querySelectorAll('.asp-api-key[data-provider]').forEach(function (b) {
            b.style.display = (b.dataset.provider === provider) ? '' : 'none';
        });
    }
    if (aiProviderEl && aiModelEl) {
        var initCurrent = aiModelEl.options.length ? aiModelEl.options[0].value : '';
        aiProviderEl.addEventListener('change', function () {
            rebuildModels(aiProviderEl.value, aiModelEl.value);
            refreshApiKeyBlocks(aiProviderEl.value);
        });
        rebuildModels(aiProviderEl.value, initCurrent);
        refreshApiKeyBlocks(aiProviderEl.value);
    }

    // ─── Test connection ──────────────────────────────────────────────────────────
    function bindTestBtn(btnId, resultId) {
        var btn = $(btnId), res = $(resultId);
        if (!btn) return;
        btn.addEventListener('click', function () {
            var u = url(btn); if (!u) return;
            btn.disabled = true;
            if (res) res.innerHTML = '<span class="ok-badge ok-badge-muted ok-badge-sm">' + (L.testing || 'Testing…') + '</span>';
            getFrom(u, function (data) {
                var ok = (data.status === 'ok' || data.status === 'running');
                var text = ok
                    ? (data.mode === 'native' ? (L.connNative || 'Native mode') : (L.connHealthy || 'Connection is healthy'))
                    : (L.connFailed || 'Connection failed');
                if (res) {
                    res.innerHTML = '<span class="ok-badge ' + (ok ? 'ok-badge-success-soft' : 'ok-badge-danger') + ' ok-badge-sm">' + text + '</span>';
                }
                notify(text, ok ? 'success' : 'error');
                btn.disabled = false;
            }, function () {
                if (res) res.innerHTML = '<span class="ok-badge ok-badge-danger ok-badge-sm">' + (L.requestFailed || 'Failed') + '</span>';
                btn.disabled = false;
            });
        });
    }
    bindTestBtn('asp-test-connection', 'asp-test-result');
    bindTestBtn('asp-stats-recheck',   'asp-stats-conn-result');

    // Auto-run the daemon connection check when the settings page opens so the
    // status is visible immediately, not only after the user clicks "Recheck".
    var aspAutoConnCheck = $('asp-stats-recheck');
    if (aspAutoConnCheck) {
        aspAutoConnCheck.click();
    }

    // ─── Generate config ──────────────────────────────────────────────────────────
    var genCfgBtn = $('asp-gen-config'), genCfgSt = $('asp-gen-config-status'), mantiConf = $('asp-manticore-conf');
    if (genCfgBtn) {
        genCfgBtn.addEventListener('click', function () {
            var u = url(genCfgBtn); if (!u) return;
            genCfgBtn.disabled = true;
            if (genCfgSt) genCfgSt.textContent = L.generating || 'Генерація…';
            getFrom(u, function (data) {
                if (mantiConf) mantiConf.value = data.config || '';
                if (genCfgSt) genCfgSt.textContent = data.status === 'ok' ? '✓' : (data.error || '');
                genCfgBtn.disabled = false;
            }, function () {
                if (genCfgSt) genCfgSt.textContent = L.requestFailed || 'Помилка';
                genCfgBtn.disabled = false;
            });
        });
    }

    // ─── Daemon control ───────────────────────────────────────────────────────────
    function refreshDaemon(d) {
        var m = { 'asp-daemon-status': d.status, 'asp-daemon-pid': d.pid, 'asp-daemon-memory': d.memory, 'asp-daemon-cpu': d.cpu };
        for (var k in m) { var e = $(k); if (e) e.value = m[k] || '-'; }
    }
    ['start', 'restart', 'stop'].forEach(function (a) {
        var btn = $('asp-daemon-' + a);
        if (!btn) return;
        btn.addEventListener('click', function () {
            var u = url(btn); if (!u) return;
            postTo(u, { action: a }, function (data) {
                refreshDaemon(data.daemon || {});
                notify(data.message || a, data.status === 'ok' ? 'success' : 'error');
            });
        });
    });
    var daemonRefresh = $('asp-daemon-refresh');
    if (daemonRefresh) {
        daemonRefresh.addEventListener('click', function () {
            var u = url(daemonRefresh); if (!u) return;
            getFrom(u, function (data) { refreshDaemon(data.daemon || data || {}); });
        });
    }

    // ─── Manual index runs ────────────────────────────────────────────────────────
    var runLog = $('asp-run-log'), runLimit = $('asp-run-limit'), runOffset = $('asp-run-offset'), runMins = $('asp-run-minutes');
    var runBtnsContainer = $('asp-run-btns');
    var reindexProgressWrap = $('asp-reindex-progress-wrap');
    var reindexProgressBar  = $('asp-reindex-progress-bar');
    var reindexProgressText = $('asp-reindex-progress-text');

    function getProgressI18n() {
        var el = reindexProgressWrap;
        return el ? {
            of:    el.dataset.textOf   || 'Оброблено',
            from:  el.dataset.textFrom || 'з',
            done:  el.dataset.textDone || 'Готово',
            native:el.dataset.textNative || ''
        } : {};
    }

    function runFullReindexPaginated() {
        var manualUrl = runBtnsContainer ? runBtnsContainer.dataset.manualRunUrl : '';
        if (!manualUrl) return;
        var limit = runLimit ? parseInt(runLimit.value, 10) || 500 : 500;
        var i18n = getProgressI18n();
        var processed = 0, total = 0, offset = 0;

        showProgress(i18n.of + '…');
        logLine(runLog, '[full] запускаємо…');

        function step() {
            postTo(manualUrl, { type: 'full', limit: limit, offset: offset }, function (data) {
                if (data.total > 0) total = data.total;
                processed += (data.processed || 0);
                offset    += limit;

                // Native mode: processed=0, total=0 on first call → no reindex needed
                if (total === 0 && data.processed === 0 && offset <= limit) {
                    logLine(runLog, '[ok] full — ' + (i18n.native || 'native mode'));
                    hideProgress(i18n.native || 'Native mode');
                    return;
                }

                // Switch to determinate bar once we have real data
                if (reindexProgressInner) reindexProgressInner.classList.remove('ok-progress-indeterminate');
                var pct = total > 0 ? Math.min(100, Math.round(processed / total * 100)) : 50;
                if (reindexProgressBar)  reindexProgressBar.style.width = pct + '%';
                if (reindexProgressText) reindexProgressText.textContent =
                    i18n.of + ' ' + processed + ' ' + i18n.from + ' ' + (total || '?');

                logLine(runLog, '[ok] full — ' + processed + (total ? ' / ' + total : ''));

                if (data.processed > 0 && (total === 0 || processed < total)) {
                    step();
                } else {
                    hideProgress(i18n.done || 'Done');
                    logLine(runLog, '[done] full reindex complete');
                }
            }, function () {
                logLine(runLog, '[error] full');
                if (reindexProgressWrap) reindexProgressWrap.classList.add('ok-hidden');
            });
        }
        step();
    }

    var reindexProgressInner = reindexProgressWrap ? reindexProgressWrap.querySelector('.ok-progress') : null;

    function showProgress(text) {
        if (!reindexProgressWrap) return;
        reindexProgressWrap.classList.remove('ok-hidden');
        if (reindexProgressInner) reindexProgressInner.classList.add('ok-progress-indeterminate');
        if (reindexProgressBar) reindexProgressBar.style.width = '100%';
        if (reindexProgressText) reindexProgressText.textContent = text || '…';
    }
    function hideProgress(doneText) {
        if (!reindexProgressWrap) return;
        if (reindexProgressInner) reindexProgressInner.classList.remove('ok-progress-indeterminate');
        if (reindexProgressBar) reindexProgressBar.style.width = '100%';
        if (reindexProgressText && doneText) reindexProgressText.textContent = doneText;
        setTimeout(function () {
            if (reindexProgressWrap) reindexProgressWrap.classList.add('ok-hidden');
        }, 3000);
    }

    function runCron(type) {
        if (type === 'full') { runFullReindexPaginated(); return; }
        var manualUrl = runBtnsContainer ? runBtnsContainer.dataset.manualRunUrl : '';
        if (!manualUrl) return;
        var body = { type: type, limit: runLimit ? runLimit.value : 500 };
        if (type === 'sync_modified') body.minutes = runMins ? runMins.value : 60;
        logLine(runLog, '[' + type + '] запускаємо…');
        showProgress(type + '…');
        postTo(manualUrl, body, function (data) {
            var processed = data.processed || 0;
            logLine(runLog, '[' + (data.status || 'ok') + '] ' + type + ' — processed: ' + processed);
            var i18n = getProgressI18n();
            hideProgress(i18n.done + ' (' + processed + ')');
        }, function () {
            logLine(runLog, '[error] ' + type);
            hideProgress('');
        });
    }

    [['asp-run-full', 'full'], ['asp-run-incremental', 'incremental'], ['asp-run-sync', 'sync_modified'],
     ['asp-run-ai-rules', 'ai_rules'], ['asp-run-warm', 'warm_cache']].forEach(function (p) {
        var btn = $(p[0]);
        if (btn) btn.addEventListener('click', function () { runCron(p[1]); });
    });

    var clearLogBtn = $('asp-clear-cron-log');
    if (clearLogBtn) {
        clearLogBtn.addEventListener('click', function () {
            var u = url(clearLogBtn); if (!u) return;
            postTo(u, {}, function () { if (runLog) runLog.value = ''; notify('Лог очищено'); });
        });
    }

    // ─── Purge log ────────────────────────────────────────────────────────────────
    var purgeBtn = $('asp-purge-log'), purgeSt = $('asp-purge-status');
    if (purgeBtn) {
        purgeBtn.addEventListener('click', function () {
            var u = url(purgeBtn); if (!u) return;
            purgeBtn.disabled = true;
            if (purgeSt) purgeSt.textContent = L.purgeLogRunning || 'Очищення…';
            postTo(u, { type: 'purge_log', days: purgeBtn.dataset.ttl || 90 }, function () {
                if (purgeSt) purgeSt.textContent = L.purgeLogDone || 'Готово';
                notify(L.purgeLogDone || 'Лог очищено');
                purgeBtn.disabled = false;
            }, function () {
                if (purgeSt) purgeSt.textContent = L.requestFailed || 'Помилка';
                purgeBtn.disabled = false;
            });
        });
    }

    // ─── Re-embed all — generate NOW with live progress (queue → poll-drain) ──────
    var reembedBtn = $('asp-reembed-all'), reembedSt = $('asp-reembed-status');
    if (reembedBtn) {
        var REEMBED_BATCH = 50; // products per request (~10s of AI calls each)
        reembedBtn.addEventListener('click', function () {
            if (!confirm(L.reembedConfirm || 'Перебудувати ембеддінги всіх товарів зараз? Це звертається до платного AI API.')) return;
            var u = url(reembedBtn); if (!u) return;
            reembedBtn.disabled = true;
            var done = 0, total = 0, t0 = Date.now();
            var setSt = function (t) { if (reembedSt) reembedSt.textContent = t; };
            var secs  = function () { return Math.round((Date.now() - t0) / 1000); };
            var fail  = function () { setSt(L.requestFailed || 'Помилка'); reembedBtn.disabled = false; };

            // 1) Queue only products missing a current-model embedding, then drain.
            setSt(L.reembedQueuing || 'Додаємо у чергу…');
            postTo(u, { type: 'embed_missing' }, function (data) {
                total = (data.pending != null) ? data.pending : (data.processed || 0);
                if (!total) { setSt(L.reembedDone || 'Готово'); reembedBtn.disabled = false; return; }
                drain();
            }, fail);

            // 2) One batch per request; recurse until the queue is empty.
            function drain() {
                postTo(u, { type: 'embedding', limit: REEMBED_BATCH }, function (data) {
                    done += (data.processed || 0);
                    var pending = (data.pending != null) ? data.pending : 0;
                    var pct = total ? Math.min(100, Math.round(done / total * 100)) : 100;
                    setSt((L.reembedRunning || 'Генерую') + ': ' + done + ' / ' + total + ' (' + pct + '%)');

                    if (pending <= 0) {
                        var failed = total - done;
                        var msg = (L.reembedDone || 'Готово') + ': ' + done + ' / ' + total +
                                  (failed > 0 ? ' (' + (L.reembedFailed || 'помилок') + ': ' + failed + ')' : '') +
                                  ' · ' + secs() + 's';
                        setSt(msg); notify(msg, failed > 0 ? 'warning' : 'success'); reembedBtn.disabled = false;
                        return;
                    }
                    if ((data.processed || 0) === 0) {
                        // Queue still has items but nothing embedded → blocked (budget / key / errors).
                        var sm = (L.reembedStopped || 'Зупинено') + ': ' + done + ' / ' + total;
                        setSt(sm); notify(sm, 'warning'); reembedBtn.disabled = false;
                        return;
                    }
                    drain();
                }, fail);
            }
        });
    }

    // ─── Benchmark ───────────────────────────────────────────────────────────────
    var benchBtn = $('asp-run-benchmark'), benchSt = $('asp-benchmark-status'), benchRes = $('asp-benchmark-result');
    var benchInterpret = $('asp-benchmark-interpret'), benchInterpretBody = $('asp-bench-interpret-body');

    function benchRating(ms, type) {
        var el = benchInterpret;
        var t = el ? { excellent: el.dataset.textExcellent, good: el.dataset.textGood, acceptable: el.dataset.textAcceptable, slow: el.dataset.textSlow } : {};
        if (type === 'native')    return ms < 50  ? [t.excellent||'Відмінно','ok-badge-success'] : ms < 150 ? [t.good||'Добре','ok-badge-info'] : ms < 300 ? [t.acceptable||'Прийнятно','ok-badge-warning'] : [t.slow||'Повільно','ok-badge-danger'];
        if (type === 'manticore') return ms < 20  ? [t.excellent||'Відмінно','ok-badge-success'] : ms < 60  ? [t.good||'Добре','ok-badge-info'] : ms < 150 ? [t.acceptable||'Прийнятно','ok-badge-warning'] : [t.slow||'Повільно','ok-badge-danger'];
        if (type === 'hybrid')    return ms < 100 ? [t.excellent||'Відмінно','ok-badge-success'] : ms < 250 ? [t.good||'Добре','ok-badge-info'] : ms < 500 ? [t.acceptable||'Прийнятно','ok-badge-warning'] : [t.slow||'Повільно','ok-badge-danger'];
        return ms < 100 ? [t.excellent||'Відмінно','ok-badge-success'] : ms < 300 ? [t.good||'Добре','ok-badge-info'] : [t.acceptable||'Прийнятно','ok-badge-warning'];
    }

    function renderBenchInterpret(data) {
        if (!benchInterpret || !benchInterpretBody) return;
        // Response structure: data.modes[mode] = { avg_ms, p95_ms, errors, ... }
        var modes = data.modes;
        if (!modes || typeof modes !== 'object') return;
        var el = benchInterpret;
        var tips = {
            native:    el.dataset.tipNative  || '',
            manticore: el.dataset.tipMant    || '',
            slow:      el.dataset.tipSlow    || '',
            hybrid:    el.dataset.tipHybrid  || ''
        };
        var rows = [];
        Object.keys(modes).forEach(function (mode) {
            var m = modes[mode];
            if (!m || m.avg_ms === undefined) return;
            var ms = parseFloat(m.avg_ms);
            if (isNaN(ms)) return;
            var r = benchRating(ms, mode);
            var tip = mode === 'hybrid' ? tips.hybrid
                : (mode === 'native' ? (ms > 200 ? tips.slow : tips.native)
                : (mode === 'manticore' ? (ms > 200 ? tips.slow : tips.manticore) : ''));
            var label = mode.charAt(0).toUpperCase() + mode.slice(1);
            rows.push('<tr>' +
                '<td class="ok-fw-600">' + label + '</td>' +
                '<td>' + ms.toFixed(1) + ' ms <span class="ok-muted ok-text-xs">(p95: ' + parseFloat(m.p95_ms || 0).toFixed(1) + ' ms)</span></td>' +
                '<td><span class="ok-badge ' + r[1] + '">' + r[0] + '</span></td>' +
                '<td class="ok-muted ok-text-sm">' + esc(tip) + '</td></tr>');
        });
        if (rows.length) {
            benchInterpretBody.innerHTML = rows.join('');
            benchInterpret.classList.remove('ok-hidden');
        }
    }

    if (benchBtn) {
        benchBtn.addEventListener('click', function () {
            var u = url(benchBtn); if (!u) return;
            benchBtn.disabled = true;
            if (benchSt) benchSt.textContent = L.benchmarkRunning || 'Запуск…';
            if (benchRes) { benchRes.style.display = ''; benchRes.textContent = '…'; }
            if (benchInterpret) benchInterpret.classList.add('ok-hidden');
            getFrom(u, function (data) {
                if (benchRes) benchRes.textContent = JSON.stringify(data, null, 2);
                if (benchSt) benchSt.textContent = '';
                renderBenchInterpret(data);
                benchBtn.disabled = false;
            }, function () {
                if (benchSt) benchSt.textContent = L.benchmarkFailed || 'Помилка';
                benchBtn.disabled = false;
            });
        });
    }

    // ─── Synonyms: add row ────────────────────────────────────────────────────────
    var synAddBtn = $('asp-syn-add-row'), synTable = $('asp-synonyms-table');
    if (synAddBtn && synTable) {
        var synIdx = Date.now();
        synAddBtn.addEventListener('click', function () {
            var empty = synTable.querySelector('#asp-syn-empty');
            if (empty) empty.remove();
            var idx = 'new_' + (synIdx++), tbody = synTable.querySelector('tbody'), tr = document.createElement('tr');
            tr.innerHTML =
                '<td><input type="text" name="synonym_groups[' + idx + '][name]" class="ok-input ok-w-200"></td>' +
                '<td><input type="text" name="synonym_groups[' + idx + '][terms]" class="ok-input"></td>' +
                '<td class="ok-text-center"><button type="button" class="ok-btn ok-btn-danger ok-btn-sm asp-syn-new-remove"><i data-lucide="trash-2"></i></button></td>';
            tbody.appendChild(tr);
            tr.querySelector('.asp-syn-new-remove').addEventListener('click', function () { tr.remove(); });
        });
    }

    // ─── Synonyms: delete existing group via AJAX ─────────────────────────────────
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.asp-syn-delete-btn');
        if (!btn) return;
        var id = btn.dataset.id, u = btn.dataset.url;
        if (!id || !u) return;
        if (!confirm(L.synDeleteConfirm || 'Видалити групу синонімів?')) return;
        btn.disabled = true;
        postTo(u, { id: id }, function (data) {
            if (data.status === 'ok') {
                var tr = btn.closest('tr');
                if (tr) tr.remove();
                notify(L.deleted || 'Видалено');
            } else {
                notify(data.message || (L.requestFailed || 'Помилка'), 'error');
                btn.disabled = false;
            }
        }, function () {
            notify(L.requestFailed || 'Помилка', 'error');
            btn.disabled = false;
        });
    });

    // ─── Synonyms: CSV upload ─────────────────────────────────────────────────────
    var synCsvUpload = $('asp-syn-csv-upload'), synCsvFile = $('syn-csv-file');
    if (synCsvUpload && synCsvFile) {
        synCsvUpload.addEventListener('click', function () {
            if (!synCsvFile.files || !synCsvFile.files[0]) {
                notify(L.csvNoFile || 'Оберіть файл CSV', 'warning'); return;
            }
            var u = synCsvUpload.dataset.url; if (!u) return;
            var fd = new FormData();
            fd.append('synonyms_csv', synCsvFile.files[0]);
            synCsvUpload.disabled = true;
            fetch(u, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.status === 'ok') {
                        notify((L.csvImported || 'Імпортовано') + ': ' + (data.imported || 0));
                        synCsvFile.value = '';
                    } else {
                        notify(data.error || (L.requestFailed || 'Помилка'), 'error');
                    }
                    synCsvUpload.disabled = false;
                })
                .catch(function () {
                    notify(L.requestFailed || 'Помилка', 'error');
                    synCsvUpload.disabled = false;
                });
        });
    }

    // ─── Synonyms: AI generate ────────────────────────────────────────────────────
    var synGenBtn = $('asp-syn-generate'), synLog = $('asp-syn-log'), synGenSt = $('asp-syn-gen-status');
    var synGenProgress = $('asp-syn-gen-progress');
    if (synGenBtn) {
        synGenBtn.addEventListener('click', function () {
            var u = url(synGenBtn); if (!u) return;
            synGenBtn.disabled = true;
            if (synGenSt) { synGenSt.className = 'ok-hidden'; synGenSt.innerHTML = ''; }
            if (synGenProgress) synGenProgress.classList.remove('ok-hidden');
            postTo(u, { limit: 80, days: 30, min_count: 2 }, function (data) {
                if (synLog) synLog.value = JSON.stringify(data, null, 2);
                if (synGenProgress) synGenProgress.classList.add('ok-hidden');
                if (synGenSt) {
                    var ok = data.status === 'ok';
                    synGenSt.className = 'ok-alert ' + (ok ? 'ok-alert-success' : 'ok-alert-danger');
                    if (ok) {
                        var tmpl = L.synonymsProposed || 'Запропоновано: {proposed}, пропущено: {skipped}';
                        synGenSt.textContent = tmpl
                            .replace('{proposed}', data.proposed || 0)
                            .replace('{skipped}',  data.skipped  || 0);
                        if ((data.proposed || 0) > 0) {
                            // Reload to show new pending proposals in the table below.
                            setTimeout(function () { window.location.reload(); }, 1500);
                        }
                    } else {
                        synGenSt.textContent = data.error || data.message || L.requestFailed || 'Помилка';
                    }
                }
                synGenBtn.disabled = false;
            }, function () {
                if (synLog) synLog.value = L.requestFailed || 'Помилка';
                if (synGenProgress) synGenProgress.classList.add('ok-hidden');
                if (synGenSt) {
                    synGenSt.className = 'ok-alert ok-alert-danger';
                    synGenSt.textContent = L.requestFailed || 'Помилка запиту';
                }
                synGenBtn.disabled = false;
            });
        });
    }

    // ─── Synonyms: pending proposals accept / reject ──────────────────────────────
    document.addEventListener('click', function (e) {
        var acc = e.target.closest('.asp-syn-accept-btn');
        var rej = e.target.closest('.asp-syn-reject-btn');
        var btn = acc || rej;
        if (!btn) return;
        var u  = btn.dataset.url; if (!u) return;
        var id = btn.dataset.id || '';
        var row = btn.closest('tr');
        btn.disabled = true;
        postTo(u, { id: id }, function (data) {
            if (data.status === 'ok') {
                if (row) row.parentNode.removeChild(row);
                var tbody = document.querySelector('#asp-syn-pending-table tbody');
                if (tbody && !tbody.querySelector('tr[data-pending-id]')) {
                    var tr = document.createElement('tr');
                    tr.id = 'asp-syn-pending-empty';
                    tr.innerHTML = '<td colspan="4" class="ok-text-muted ok-text-center">' + (L.noPendingSynonyms || '—') + '</td>';
                    tbody.appendChild(tr);
                }
                if (window.okNotify) {
                    window.okNotify(acc ? (L.synonymAccepted || 'Прийнято') : (L.synonymRejected || 'Відхилено'), 'success');
                }
            } else {
                btn.disabled = false;
                if (window.okNotify) {
                    window.okNotify(data.message || L.requestFailed || 'Помилка', 'error');
                }
            }
        }, function () {
            btn.disabled = false;
            if (window.okNotify) {
                window.okNotify(L.requestFailed || 'Помилка', 'error');
            }
        });
    });

    // ─── Synonyms: load bundled brand pack ─────────────────────────────────────────
    var synBundledBtn = $('asp-syn-load-bundled');
    var synBundledSt  = $('asp-syn-bundled-status');
    if (synBundledBtn) {
        synBundledBtn.addEventListener('click', function () {
            var u = url(synBundledBtn); if (!u) return;
            synBundledBtn.disabled = true;
            if (synBundledSt) {
                synBundledSt.className = 'ok-alert ok-alert-info';
                synBundledSt.textContent = L.bundledLoading || 'Loading…';
            }
            postTo(u, {}, function (data) {
                var ok = data.status === 'ok';
                if (synBundledSt) {
                    synBundledSt.className = 'ok-alert ' + (ok ? 'ok-alert-success' : 'ok-alert-danger');
                    if (ok) {
                        var tmpl = L.bundledDone || 'Done: created {created}, skipped {skipped} of {total}';
                        synBundledSt.textContent = tmpl
                            .replace('{created}', data.created || 0)
                            .replace('{skipped}', data.skipped || 0)
                            .replace('{total}',   data.total   || 0);
                        if ((data.created || 0) > 0) {
                            // Reload so the new groups appear in the synonyms table.
                            setTimeout(function () { window.location.reload(); }, 1200);
                        }
                    } else {
                        synBundledSt.textContent = data.message || L.requestFailed || 'Failed';
                    }
                }
                synBundledBtn.disabled = false;
            }, function () {
                if (synBundledSt) {
                    synBundledSt.className = 'ok-alert ok-alert-danger';
                    synBundledSt.textContent = L.requestFailed || 'Failed';
                }
                synBundledBtn.disabled = false;
            });
        });
    }

    // ─── Niche presets (Clothes / Electronics / Food / …) ─────────────────────────
    var presetStatus = $('asp-preset-status');
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.asp-preset-btn'); if (!btn) return;
        var wrap = btn.closest('[data-preset-url]');
        var u = wrap ? wrap.dataset.presetUrl : ''; if (!u) return;
        var code = btn.dataset.code || '';
        document.querySelectorAll('.asp-preset-btn').forEach(function (b) { b.disabled = true; });
        if (presetStatus) {
            presetStatus.className = 'ok-alert ok-alert-info ok-mt-2';
            presetStatus.textContent = L.presetLoading || 'Applying preset…';
        }
        postTo(u, { code: code }, function (data) {
            var ok = data.status === 'ok';
            if (presetStatus) {
                presetStatus.className = 'ok-alert ' + (ok ? 'ok-alert-success' : 'ok-alert-danger') + ' ok-mt-2';
                if (ok) {
                    var tmpl = L.presetDone || 'Preset "{name}" applied: +{added} syn, skipped {skipped}, settings {settings}';
                    presetStatus.textContent = tmpl
                        .replace('{name}',     data.name             || code)
                        .replace('{added}',    data.synonyms_added   || 0)
                        .replace('{skipped}',  data.synonyms_skipped || 0)
                        .replace('{settings}', Object.keys(data.settings_applied || {}).length);
                    // Reload after a beat so the new groups + applied settings show up.
                    setTimeout(function () { window.location.reload(); }, 1500);
                } else {
                    presetStatus.textContent = data.message || L.requestFailed || 'Failed';
                    document.querySelectorAll('.asp-preset-btn').forEach(function (b) { b.disabled = false; });
                }
            }
        }, function () {
            if (presetStatus) {
                presetStatus.className = 'ok-alert ok-alert-danger ok-mt-2';
                presetStatus.textContent = L.requestFailed || 'Failed';
            }
            document.querySelectorAll('.asp-preset-btn').forEach(function (b) { b.disabled = false; });
        });
    });

    // ─── Attributes ──────────────────────────────────────────────────────────────
    var attrRaw = $('asp-attributes-raw'), attrSelTable = $('asp-selected-attrs-table');

    // attrRaw stores newline-separated rows: "id,type,is_filter,is_search"
    function getAttrRows() {
        if (!attrRaw) return [];
        return attrRaw.value.split(/\r?\n/).map(function (s) { return s.trim(); }).filter(Boolean);
    }
    function getAttrIds() {
        return getAttrRows().map(function (row) { return row.split(',')[0].trim(); }).filter(Boolean);
    }
    function addAttrRow(id) {
        if (!attrRaw) return;
        var rows = getAttrRows().filter(function (row) { return row.split(',')[0].trim() !== String(id); });
        rows.push(String(id) + ',text,1,1');
        attrRaw.value = rows.join('\n');
    }
    function removeAttrRow(id) {
        if (!attrRaw) return;
        var rows = getAttrRows().filter(function (row) { return row.split(',')[0].trim() !== String(id); });
        attrRaw.value = rows.join('\n');
    }

    // ─── Popular tags ─────────────────────────────────────────────────────────
    var tagsRaw   = $('asp-popular-tags-raw');
    var tagsBody  = $('asp-popular-tags-body');
    var tagsAddBtn= $('asp-popular-tags-add');

    function tagsRow(item) {
        item = item || {};
        var names = item.names || {};
        var urls  = item.urls  || {};
        var tr = document.createElement('tr');
        tr.innerHTML =
            '<td><input type="text" class="ok-input tag-name-uk" value="' + esc(names.uk || '') + '" style="min-width:90px"></td>' +
            '<td><input type="text" class="ok-input tag-name-ru" value="' + esc(names.ru || '') + '" style="min-width:90px"></td>' +
            '<td><input type="text" class="ok-input tag-name-en" value="' + esc(names.en || '') + '" style="min-width:90px"></td>' +
            '<td><input type="text" class="ok-input tag-url-uk"  value="' + esc(urls.uk  || '') + '" style="min-width:120px"></td>' +
            '<td><input type="text" class="ok-input tag-url-ru"  value="' + esc(urls.ru  || '') + '" style="min-width:120px"></td>' +
            '<td><input type="text" class="ok-input tag-url-en"  value="' + esc(urls.en  || '') + '" style="min-width:120px"></td>' +
            '<td><button type="button" class="ok-btn ok-btn-danger ok-btn-sm ok-btn-icon asp-tag-del"><i data-lucide="trash-2"></i></button></td>';
        tr.querySelectorAll('input').forEach(function (inp) { inp.addEventListener('input', serializeTags); });
        tr.querySelector('.asp-tag-del').addEventListener('click', function () { tr.remove(); serializeTags(); });
        return tr;
    }

    function serializeTags() {
        if (!tagsBody || !tagsRaw) return;
        var rows = tagsBody.querySelectorAll('tr');
        var list = [];
        rows.forEach(function (tr) {
            var uk = tr.querySelector('.tag-name-uk'), ru = tr.querySelector('.tag-name-ru'), en = tr.querySelector('.tag-name-en');
            var uu = tr.querySelector('.tag-url-uk'),  ru2 = tr.querySelector('.tag-url-ru'), eu = tr.querySelector('.tag-url-en');
            if (!uk) return;
            list.push({
                names: { uk: uk.value.trim(), ru: ru ? ru.value.trim() : '', en: en ? en.value.trim() : '' },
                urls:  { uk: uu ? uu.value.trim() : '', ru: ru2 ? ru2.value.trim() : '', en: eu ? eu.value.trim() : '' }
            });
        });
        tagsRaw.value = list.length ? JSON.stringify(list) : '';
    }

    if (tagsBody) {
        var tagsInitRaw = tagsRaw ? tagsRaw.value.trim() : '';
        if (tagsInitRaw) {
            try {
                var initTags = JSON.parse(tagsInitRaw);
                if (Array.isArray(initTags)) {
                    initTags.forEach(function (item) { tagsBody.appendChild(tagsRow(item)); });
                }
            } catch (e) { /* ignore bad JSON */ }
        }
    }
    if (tagsAddBtn) {
        tagsAddBtn.addEventListener('click', function () {
            if (tagsBody) { tagsBody.appendChild(tagsRow()); serializeTags(); }
        });
    }

    document.querySelectorAll('.asp-attr-toggle').forEach(function (t) {
        t.checked = getAttrIds().indexOf(t.dataset.id) !== -1;
    });

    document.addEventListener('change', function (e) {
        var t = e.target.closest('.asp-attr-toggle');
        if (!t) return;
        var id = t.dataset.id;
        if (t.checked) {
            addAttrRow(id);
            if (attrSelTable) {
                var empty = attrSelTable.querySelector('#asp-attrs-empty');
                if (empty) empty.remove();
                var tbody = attrSelTable.querySelector('tbody');
                if (!tbody.querySelector('[data-id="' + id + '"]')) {
                    var tr = document.createElement('tr');
                    tr.setAttribute('data-id', id);
                    tr.innerHTML = '<td>' + esc(id) + '</td><td>' + esc(t.dataset.name) + '</td><td>' + esc(t.dataset.group || '') + '</td>' +
                        '<td class="ok-text-center"><button type="button" class="ok-btn ok-btn-danger ok-btn-sm asp-attr-remove" data-id="' + esc(id) + '"><i data-lucide="x"></i></button></td>';
                    tbody.appendChild(tr);
                }
            }
        } else {
            removeAttrRow(id);
            if (attrSelTable) { var row = attrSelTable.querySelector('[data-id="' + id + '"]'); if (row) row.remove(); }
        }
    });

    // ─── Attributes: remove button ───────────────────────────────────────────────
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.asp-attr-remove');
        if (!btn) return;
        var id = btn.dataset.id;
        removeAttrRow(id);
        var row = attrSelTable && attrSelTable.querySelector('[data-id="' + id + '"]');
        if (row) row.remove();
        // uncheck the toggle in the catalog list if visible
        var toggle = document.querySelector('.asp-attr-toggle[data-id="' + id + '"]');
        if (toggle) toggle.checked = false;
    });

    // ─── Attributes: search autocomplete ─────────────────────────────────────────
    var aspAttrSearch = $('asp-attr-search');
    var aspAttrDropdown = $('asp-attr-dropdown');
    var aspAvailableAttrs = [];

    if (aspAttrSearch && aspAttrDropdown) {
        var aspAttrUrl = aspAttrSearch.dataset.url || '';

        function aspShowDropdown(q) {
            var list = q ? aspAvailableAttrs.filter(function (a) {
                return ((a.group_name ? a.group_name + ' › ' : '') + a.name).toLowerCase().indexOf(q.toLowerCase()) !== -1;
            }) : aspAvailableAttrs;
            var matches = list.slice(0, 20);
            if (!matches.length) { aspAttrDropdown.style.display = 'none'; return; }
            aspAttrDropdown.innerHTML = '';
            matches.forEach(function (a) {
                var label = (a.group_name ? a.group_name + ' › ' : '') + a.name;
                var item = document.createElement('div');
                item.className = 'ok-dropdown-item';
                item.textContent = label;
                item.addEventListener('mousedown', function (ev) {
                    ev.preventDefault();
                    aspAttrSearch.value = label;
                    aspAttrDropdown.style.display = 'none';
                    // simulate toggle on
                    var toggle = document.querySelector('.asp-attr-toggle[data-id="' + a.attribute_id + '"]');
                    if (toggle && !toggle.checked) {
                        toggle.checked = true;
                        toggle.dispatchEvent(new Event('change', { bubbles: true }));
                    } else if (!toggle) {
                        addAttrRow(a.attribute_id);
                        if (attrSelTable) {
                            var empty = attrSelTable.querySelector('#asp-attrs-empty');
                            if (empty) empty.remove();
                            var tbody = attrSelTable.querySelector('tbody');
                            if (!tbody.querySelector('[data-id="' + a.attribute_id + '"]')) {
                                var tr = document.createElement('tr');
                                tr.setAttribute('data-id', a.attribute_id);
                                tr.innerHTML = '<td>' + esc(a.attribute_id) + '</td><td>' + esc(a.name) + '</td><td>' + esc(a.group_name || '') + '</td>' +
                                    '<td class="ok-text-center"><button type="button" class="ok-btn ok-btn-danger ok-btn-sm asp-attr-remove" data-id="' + esc(a.attribute_id) + '"><i data-lucide="x"></i></button></td>';
                                tbody.appendChild(tr);
                            }
                        }
                    }
                    aspAttrSearch.value = '';
                });
                aspAttrDropdown.appendChild(item);
            });
            aspAttrDropdown.style.display = 'block';
        }

        var aspAttrTimer = null;
        aspAttrSearch.addEventListener('input', function () {
            var q = aspAttrSearch.value.trim();
            if (!q) { aspAttrDropdown.style.display = 'none'; return; }
            if (!aspAttrUrl) return;
            clearTimeout(aspAttrTimer);
            aspAttrTimer = setTimeout(function () {
                fetch(aspAttrUrl + '&q=' + encodeURIComponent(q), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.json(); })
                    .then(function (data) { aspAvailableAttrs = data.items || []; aspShowDropdown(q); })
                    .catch(function () {});
            }, 250);
        });

        aspAttrSearch.addEventListener('blur', function () {
            setTimeout(function () { aspAttrDropdown.style.display = 'none'; }, 150);
        });
    }

    // ─── Query Rules ──────────────────────────────────────────────────────────────
    var qrTable = $('asp-qr-table'), qrSearch = $('asp-qr-search');
    var qrLoadBtn = $('asp-qr-load'), qrGenBtn = $('asp-qr-generate');

    function sourceBadge(src) {
        if (!src) return '<span class="ok-badge ok-badge-muted">—</span>';
        var s = String(src).toLowerCase();
        var cls = 'ok-badge-muted';
        if (s === 'ai')      cls = 'ok-badge-info-soft';
        else if (s === 'manual') cls = 'ok-badge-success-soft';
        else if (s === 'auto')   cls = 'ok-badge-warning-soft';
        else if (s === 'import') cls = 'ok-badge-primary';
        return '<span class="ok-badge ' + cls + '">' + esc(src) + '</span>';
    }

    function loadQueryRules() {
        var u = url(qrLoadBtn); if (!u || !qrTable) return;
        var tbody = qrTable.querySelector('tbody');
        if (tbody) tbody.innerHTML = '<tr><td colspan="7" class="ok-text-center ok-text-muted">' + (L.qrLoading || 'Завантаження…') + '</td></tr>';
        postTo(u, { search: qrSearch ? qrSearch.value : '' }, function (data) {
            if (!tbody) return;
            if (!data.items || !data.items.length) {
                tbody.innerHTML = '<tr><td colspan="7" class="ok-text-center ok-text-muted">' + (L.qrEmpty || 'Правил не знайдено') + '</td></tr>';
                return;
            }
            tbody.innerHTML = '';
            data.items.forEach(function (r) {
                var tr = document.createElement('tr');
                tr.setAttribute('data-id', r.id);
                tr.innerHTML =
                    '<td><div class="ok-editable" data-field="query">'    + esc(r.query)        + '</div></td>' +
                    '<td><div class="ok-editable" data-field="rewrite">'  + esc(r.rewrite  || '') + '</div></td>' +
                    '<td><div class="ok-editable" data-field="expanded">' + esc(r.expanded || '') + '</div></td>' +
                    '<td>' + sourceBadge(r.source) + '</td><td>' + (r.hits || 0) + '</td><td>' + esc(r.updated || '') + '</td>' +
                    '<td class="ok-nowrap">' +
                      '<button type="button" class="ok-btn ok-btn-default ok-btn-sm ok-btn-icon asp-qr-edit"><i data-lucide="pencil"></i></button> ' +
                      '<button type="button" class="ok-btn ok-btn-danger ok-btn-sm ok-btn-icon asp-qr-delete"><i data-lucide="trash-2"></i></button>' +
                    '</td>';
                tbody.appendChild(tr);
            });
        }, function () {
            if (tbody) tbody.innerHTML = '<tr><td colspan="7" class="ok-text-center ok-text-muted">' + (L.requestFailed || 'Помилка') + '</td></tr>';
        });
    }

    if (qrLoadBtn) qrLoadBtn.addEventListener('click', loadQueryRules);
    if (qrSearch)  qrSearch.addEventListener('keydown', function (e) { if (e.key === 'Enter') loadQueryRules(); });

    // Auto-load rules when the Query Rules tab becomes active
    var qrTabBtn = document.querySelector('[data-tab="tab-query-rules"]');
    var qrLoaded = false;
    if (qrTabBtn) {
        qrTabBtn.addEventListener('click', function () {
            if (!qrLoaded) { qrLoaded = true; loadQueryRules(); }
        });
    }

    if (qrGenBtn) {
        qrGenBtn.addEventListener('click', function () {
            var u = url(qrGenBtn); if (!u) return;
            qrGenBtn.disabled = true;
            postTo(u, { limit: 100, days: 30, min_count: 2 }, function (data) {
                notify((data.status === 'ok' ? '✓ ' : '') + (data.processed || ''), data.status === 'ok' ? 'success' : 'error');
                loadQueryRules();
                qrGenBtn.disabled = false;
            }, function () {
                notify(L.requestFailed || 'Помилка', 'error');
                qrGenBtn.disabled = false;
            });
        });
    }

    if (qrTable) {
        qrTable.addEventListener('click', function (e) {
            var tr = e.target.closest('tr[data-id]'); if (!tr) return;
            var id = tr.getAttribute('data-id');

            if (e.target.closest('.asp-qr-delete')) {
                if (!confirm(L.qrDeleteConfirm || 'Видалити це правило?')) return;
                var delUrl = qrTable.dataset.deleteUrl;
                if (!delUrl) return;
                postTo(delUrl, { id: id }, function (_) { tr.remove(); notify(L.qrDeleted || 'Видалено'); });
                return;
            }

            if (e.target.closest('.asp-qr-edit') && !tr.classList.contains('asp-editing')) {
                tr.classList.add('asp-editing');
                tr.querySelectorAll('.ok-editable').forEach(function (cell) {
                    cell.innerHTML = '<input type="text" class="ok-input" style="width:100%;min-width:80px" value="' + esc(cell.textContent.trim()) + '">';
                });
                var actCell = tr.querySelector('td:last-child');
                if (actCell) {
                    var saveBtn = document.createElement('button');
                    saveBtn.type = 'button';
                    saveBtn.className = 'ok-btn ok-btn-primary ok-btn-sm ok-btn-icon ok-mr-1 asp-qr-save';
                    saveBtn.innerHTML = '<i data-lucide="check"></i>';
                    saveBtn.addEventListener('click', function () {
                        var updUrl = qrTable.dataset.updateUrl; if (!updUrl) return;
                        var body = { id: id };
                        tr.querySelectorAll('.ok-editable').forEach(function (cell) { body[cell.dataset.field] = cell.querySelector('input').value; });
                        postTo(updUrl, body, function () {
                            tr.classList.remove('asp-editing');
                            tr.querySelectorAll('.ok-editable').forEach(function (cell) { cell.textContent = cell.querySelector('input') ? cell.querySelector('input').value : cell.textContent; });
                            saveBtn.remove();
                            notify(L.qrSaved || 'Збережено ✓');
                        });
                    });
                    actCell.prepend(saveBtn);
                }
            }
        });
    }

    var qrPreviewBtn = $('asp-qr-preview-btn'), qrPreviewQ = $('asp-qr-preview-q'), qrPreviewRes = $('asp-qr-preview-result');
    if (qrPreviewBtn) {
        qrPreviewBtn.addEventListener('click', function () {
            var u = url(qrPreviewBtn); if (!u) return;
            var q = qrPreviewQ ? qrPreviewQ.value.trim() : ''; if (!q) return;
            qrPreviewBtn.disabled = true;
            if (qrPreviewRes) { qrPreviewRes.style.display = ''; qrPreviewRes.textContent = L.previewQuerying || 'Пошук…'; }
            postTo(u, { query: q }, function (data) {
                if (qrPreviewRes) qrPreviewRes.textContent = JSON.stringify(data, null, 2);
                qrPreviewBtn.disabled = false;
            }, function () {
                if (qrPreviewRes) qrPreviewRes.textContent = L.requestFailed || 'Помилка';
                qrPreviewBtn.disabled = false;
            });
        });
    }

    // ─── Stats: clear query log ───────────────────────────────────────────────
    var clearQLogBtn = $('asp-clear-query-log');
    if (clearQLogBtn) {
        clearQLogBtn.addEventListener('click', function () {
            if (!confirm('Очистити всю статистику запитів?')) return;
            var u = url(clearQLogBtn); if (!u) return;
            postTo(u, {}, function (data) {
                if (data.status === 'ok') {
                    notify('Статистику очищено');
                    window.location.reload();
                }
            });
        });
    }

    // ─── Stats: generate rule for zero query ──────────────────────────────────────
    var zeroQTable = $('asp-zero-queries-table');
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.asp-gen-rule-for'); if (!btn) return;
        // Read the endpoint from the closest table — supports multiple no-result
        // tables (Top zero queries + All zero queries) without duplicate IDs.
        var t = btn.closest('table');
        var u = (t && t.dataset && t.dataset.url) ? t.dataset.url
              : (zeroQTable ? zeroQTable.dataset.url : '');
        if (!u) return;
        btn.disabled = true;
        btn.textContent = L.generatingRule || 'Генерація…';
        postTo(u, { query: btn.dataset.query }, function () {
            btn.textContent = L.ruleGenerated || 'Готово ✓';
            notify(L.ruleGenerated || 'Правило створено');
        }, function () {
            btn.textContent = L.requestFailed || 'Помилка';
            btn.disabled = false;
        });
    });

    // ─── Tab "All zero-result queries": bulk selection + bulk generate ───────────
    var zeroAllTable = $('asp-all-zero-table');
    var zeroBulkBar   = $('asp-zero-bulk-bar');
    var zeroBulkCount = $('asp-zero-bulk-count');
    var zeroBulkBtn   = $('asp-zero-bulk-generate');
    var zeroCheckAll  = $('asp-zero-check-all');

    function zeroUpdateBulk() {
        if (!zeroAllTable) return;
        var n = zeroAllTable.querySelectorAll('.asp-zero-row-check:checked').length;
        if (zeroBulkCount) zeroBulkCount.textContent = (L.selectedNQueries || 'Selected:') + ' ' + n;
        if (zeroBulkBar)   zeroBulkBar.classList.toggle('ok-hidden', n === 0);
        if (zeroCheckAll) {
            var rows = zeroAllTable.querySelectorAll('.asp-zero-row-check:not(:disabled)');
            zeroCheckAll.checked = rows.length > 0 && n === rows.length;
            zeroCheckAll.indeterminate = n > 0 && n < rows.length;
        }
    }
    if (zeroCheckAll && zeroAllTable) {
        zeroCheckAll.addEventListener('change', function () {
            zeroAllTable.querySelectorAll('.asp-zero-row-check:not(:disabled)').forEach(function (cb) {
                cb.checked = zeroCheckAll.checked;
            });
            zeroUpdateBulk();
        });
    }
    if (zeroAllTable) {
        zeroAllTable.addEventListener('change', function (e) {
            if (e.target && e.target.classList.contains('asp-zero-row-check')) zeroUpdateBulk();
        });
    }
    if (zeroBulkBtn) {
        zeroBulkBtn.addEventListener('click', function () {
            var u = url(zeroBulkBtn); if (!u || !zeroAllTable) return;
            var rows = [].slice.call(zeroAllTable.querySelectorAll('.asp-zero-row-check:checked'));
            if (!rows.length) return;
            zeroBulkBtn.disabled = true;
            var done = 0, failed = 0;
            function next() {
                var cb = rows.shift();
                if (!cb) {
                    notify((L.bulkGenerated || 'Generated') + ': ' + done + (failed ? (', failed ' + failed) : ''));
                    setTimeout(function () { window.location.reload(); }, 800);
                    return;
                }
                var tr = cb.closest('tr');
                var q  = tr ? tr.dataset.query : '';
                var actionBtn = tr ? tr.querySelector('.asp-gen-rule-for') : null;
                if (actionBtn) { actionBtn.disabled = true; actionBtn.textContent = L.generatingRule || 'Генерація…'; }
                postTo(u, { query: q }, function () {
                    done++;
                    if (actionBtn) actionBtn.textContent = L.ruleGenerated || 'Готово ✓';
                    next();
                }, function () { failed++; next(); });
            }
            next();
        });
    }

    // ─── Stats: no-results trend (auto-load) ─────────────────────────────────────
    var trendChart = $('asp-trend-chart');
    function loadTrend() {
        if (!trendChart || trendChart.dataset.loaded) return;
        var u = trendChart.dataset.url; if (!u) return;
        trendChart.dataset.loaded = '1';
        getFrom(u, function (data) {
            if (!data.data || !data.data.length) {
                trendChart.textContent = L.trendNoData || 'Немає даних.'; return;
            }
            var h = '<table class="ok-table"><thead><tr><th>' + esc(L.trendDate || 'Дата') + '</th><th>' + esc(L.trendTotal || 'Всього') + '</th><th>' + esc(L.trendZero || 'Без результатів') + '</th><th>%</th></tr></thead><tbody>';
            data.data.forEach(function (r) { h += '<tr><td>' + esc(r.date) + '</td><td>' + r.total + '</td><td>' + r.zero + '</td><td>' + r.pct + '</td></tr>'; });
            trendChart.innerHTML = h + '</tbody></table>';
        }, function () { if (trendChart) trendChart.textContent = L.requestFailed || 'Помилка'; });
    }
    // Auto-load when stats tab is first activated
    document.addEventListener('click', function (e) {
        if (e.target && e.target.dataset && e.target.dataset.tab === 'tab-stats') loadTrend();
    });
    // Also load if stats tab is already active on page load
    if (document.querySelector('.ok-tabs-sidebar-item.active[data-tab="tab-stats"]')) loadTrend();

    // ── Dictionary tab ──────────────────────────────────────────────────────
    (function () {
        var importBtn   = document.getElementById('dict-import-btn');
        var fileInput   = document.getElementById('dict-file-input');
        var langSelect  = document.getElementById('dict-lang-select');
        var resultSpan  = document.getElementById('dict-import-result');
        var statsDiv    = document.getElementById('dict-stats');
        var previewWrap = document.getElementById('dict-preview-wrap');
        var previewBtn  = document.getElementById('dict-preview-btn');
        var previewLang = document.getElementById('dict-preview-lang');
        var previewBody = document.getElementById('dict-preview-body');
        var previewTotal = document.getElementById('dict-preview-total');
        var previewPager = document.getElementById('dict-preview-pager');

        var importUrl  = L.dict_import_url  || '';
        var deleteUrl  = L.dict_delete_url  || '';
        var entriesUrl = L.dict_entries_url || '';

        function refreshStats(counts) {
            if (!statsDiv || !counts) return;
            var html = '<table class="ok-table ok-table--compact"><thead><tr><th>' +
                esc(L.dict_col_lang || 'Мова') + '</th><th>' +
                esc(L.dict_col_count || 'Слів') + '</th><th></th></tr></thead><tbody>';
            var langs = Object.keys(counts);
            if (!langs.length) {
                html += '<tr><td colspan="3" class="ok-text-muted">' + esc(L.dict_empty || 'Порожньо') + '</td></tr>';
            } else {
                langs.forEach(function (k) {
                    html += '<tr><td>' + esc(k || (L.dict_lang_all || 'Усі')) + '</td>' +
                        '<td><span class="ok-badge">' + counts[k] + '</span></td>' +
                        '<td><button type="button" class="ok-btn ok-btn-sm ok-btn-danger dict-delete-btn" data-lang="' + esc(k) + '">' +
                        esc(L.button_delete || 'Видалити') + '</button></td></tr>';
                });
            }
            statsDiv.innerHTML = html + '</tbody></table>';
        }

        if (importBtn) {
            importBtn.addEventListener('click', function () {
                if (!fileInput || !fileInput.files.length) {
                    if (typeof window.okNotify === 'function') window.okNotify(L.dict_no_file || 'Оберіть файл', 'warning'); return;
                }
                var fd = new FormData();
                fd.append('dict_file', fileInput.files[0]);
                fd.append('dict_lang', langSelect ? langSelect.value : '');
                importBtn.disabled = true;
                if (resultSpan) resultSpan.textContent = '...';
                fetch(importUrl, { method: 'POST', body: fd })
                    .then(function (r) { return r.json(); })
                    .then(function (d) {
                        importBtn.disabled = false;
                        if (d.status === 'ok') {
                            var msg = (L.dict_imported || 'Імпортовано: ') + d.imported;
                            if (resultSpan) resultSpan.textContent = msg;
                            if (typeof window.okNotify === 'function') window.okNotify(msg, 'success');
                            refreshStats(d.counts);
                            if (previewWrap) previewWrap.style.display = '';
                        } else {
                            var err = d.message || 'Error';
                            if (resultSpan) resultSpan.textContent = err;
                            if (typeof window.okNotify === 'function') window.okNotify(err, 'error');
                        }
                    })
                    .catch(function () { importBtn.disabled = false; if (typeof window.okNotify === 'function') window.okNotify(L.requestFailed || 'Помилка', 'error'); });
            });
        }

        document.addEventListener('click', function (e) {
            if (!e.target.classList.contains('dict-delete-btn')) return;
            var lang = e.target.dataset.lang || '';
            var msg = lang
                ? (L.dict_confirm_delete_lang || 'Видалити для мови: ') + lang + '?'
                : (L.dict_confirm_delete_all  || 'Видалити ВСІ записи словника?');
            if (!confirm(msg)) return;
            fetch(deleteUrl, { method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'lang=' + encodeURIComponent(lang) })
            .then(function (r) { return r.json(); })
            .then(function (d) { if (d.status === 'ok') { if (typeof window.okNotify === 'function') window.okNotify(L.dict_deleted || 'Видалено', 'success'); refreshStats(d.counts); } });
        });

        function loadPreview(lang, page) {
            if (!previewBody) return;
            var u = entriesUrl + '&lang=' + encodeURIComponent(lang || '') + '&page=' + (page || 1);
            fetch(u).then(function (r) { return r.json(); }).then(function (d) {
                if (d.status !== 'ok') return;
                if (previewTotal) previewTotal.textContent = '(' + d.total + ')';
                if (previewWrap) previewWrap.style.display = '';
                var html = '';
                (d.entries || []).forEach(function (row) {
                    html += '<tr><td>' + esc(row.word) + '</td><td>' + esc(row.stem) + '</td><td>' + esc(row.language) + '</td></tr>';
                });
                previewBody.innerHTML = html || ('<tr><td colspan="3">' + esc(L.dict_empty || 'Порожньо') + '</td></tr>');
                if (previewPager) previewPager.textContent = (L.dict_page || 'Стор.') + ' ' + d.page + ' / ' + d.pages;
            });
        }

        if (previewBtn) {
            previewBtn.addEventListener('click', function () {
                loadPreview(previewLang ? previewLang.value : '', 1);
            });
        }
    }());

    // ─── Popular products / brands picker (autocomplete + chips + CSV serialize) ──
    (function () {
        document.querySelectorAll('.asp-picker').forEach(function (picker) {
            var raw      = picker.querySelector('.asp-picker-raw');
            var input    = picker.querySelector('.asp-picker-input');
            var dropdown = picker.querySelector('.asp-picker-dropdown');
            var chips    = picker.querySelector('.asp-picker-chips');
            var url      = picker.dataset.url || '';
            if (!raw || !input || !dropdown || !chips) { return; }

            function serialize() {
                var ids = [];
                chips.querySelectorAll('.asp-picker-chip').forEach(function (li) {
                    var id = li.getAttribute('data-id');
                    if (id && ids.indexOf(id) === -1) { ids.push(id); }
                });
                raw.value = ids.join(',');
            }

            function addChip(id, name) {
                id = String(id);
                if (chips.querySelector('.asp-picker-chip[data-id="' + id + '"]')) { return; } // no dupes
                var li = document.createElement('li');
                li.className = 'asp-picker-chip';
                li.setAttribute('data-id', id);
                li.setAttribute('draggable', 'true');
                var t = document.createElement('span'); t.className = 'asp-picker-chip-t'; t.textContent = name;
                var x = document.createElement('button'); x.type = 'button'; x.className = 'asp-picker-x'; x.setAttribute('aria-label', 'remove');
                var ic = document.createElement('i'); ic.setAttribute('data-lucide', 'x'); x.appendChild(ic);
                li.appendChild(t); li.appendChild(x);
                chips.appendChild(li);
                serialize();
                if (window.lucide) { lucide.createIcons(); }
            }

            function hideDropdown() { dropdown.style.display = 'none'; dropdown.innerHTML = ''; }

            function showDropdown(items) {
                dropdown.innerHTML = '';
                if (!items || !items.length) { hideDropdown(); return; }
                items.slice(0, 20).forEach(function (it) {
                    var d = document.createElement('div');
                    d.className = 'ok-dropdown-item';
                    d.textContent = it.name + ' (#' + it.id + ')';
                    d.addEventListener('mousedown', function (ev) {
                        ev.preventDefault();
                        addChip(it.id, it.name);
                        input.value = '';
                        hideDropdown();
                    });
                    dropdown.appendChild(d);
                });
                dropdown.style.display = 'block';
            }

            var timer = null;
            input.addEventListener('input', function () {
                var q = input.value.trim();
                if (!q || !url) { hideDropdown(); return; }
                clearTimeout(timer);
                timer = setTimeout(function () {
                    fetch(url + '&q=' + encodeURIComponent(q), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(function (r) { return r.json(); })
                        .then(function (d) { showDropdown(d.items || []); })
                        .catch(function () { hideDropdown(); });
                }, 250);
            });
            input.addEventListener('blur', function () { setTimeout(hideDropdown, 150); });

            chips.addEventListener('click', function (e) {
                var btn = e.target.closest('.asp-picker-x');
                if (!btn) { return; }
                var li = btn.closest('.asp-picker-chip');
                if (li) { li.remove(); serialize(); }
            });

            // Drag to reorder — saved order is the display order.
            var dragEl = null;
            chips.addEventListener('dragstart', function (e) {
                var li = e.target.closest('.asp-picker-chip');
                if (!li) { return; }
                dragEl = li; li.classList.add('asp-picker-chip--drag');
            });
            chips.addEventListener('dragend', function () {
                if (dragEl) { dragEl.classList.remove('asp-picker-chip--drag'); }
                dragEl = null; serialize();
            });
            chips.addEventListener('dragover', function (e) {
                if (!dragEl) { return; }
                e.preventDefault();
                var li = e.target.closest('.asp-picker-chip');
                if (!li || li === dragEl) { return; }
                var rect = li.getBoundingClientRect();
                var after = (e.clientY - rect.top) > rect.height / 2;
                chips.insertBefore(dragEl, after ? li.nextSibling : li);
            });

            // Init: make server-rendered chips draggable + normalise the hidden CSV.
            chips.querySelectorAll('.asp-picker-chip').forEach(function (li) { li.setAttribute('draggable', 'true'); });
            serialize();
        });
    }());

}());
