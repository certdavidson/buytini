// Auto Related Products Admin | © 2026 oc-kit.com | https://oc-kit.com
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        var cfg    = window.arConfig       || {};
        var i18n   = window.arI18n        || {};
        var srcLbl = window.arSourceLabels || {};

        if (window.lucide) lucide.createIcons();

        // ── Tab switching (via ok-common.js) ─────────────────────────────────

        if (window.initTabs) {
            initTabs('#ar .ok-tabs-sidebar', '.ok-tabs-sidebar-item', '.ok-layout-panel');
        }

        // Side-effect: load data when specific tabs become active
        document.querySelectorAll('#ar .ok-tabs-sidebar-item').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var tab = btn.dataset.tab;
                if (tab === 'layout-stats')       loadStats();
                if (tab === 'layout-rules')       loadRules();
            });
        });

        // ── Slider ↔ number sync ──────────────────────────────────────────────

        var signals = ['category', 'name', 'neighbor_id', 'fields', 'manufacturer', 'attributes', 'coorders', 'price_range'];

        signals.forEach(function (sig) {
            var range = document.getElementById('ar-w-' + sig);
            var num   = document.getElementById('ar-wn-' + sig);
            if (!range || !num) return;

            range.addEventListener('input', function () { num.value = range.value; });
            num.addEventListener('input', function () {
                var v = Math.min(100, Math.max(0, parseInt(num.value, 10) || 0));
                range.value = v;
                num.value   = v;
            });
        });

        // ── Neighbor ID toggle ────────────────────────────────────────────────

        var neighborEnabled = document.getElementById('ar-neighbor-enabled');
        var neighborConfig  = document.getElementById('ar-neighbor-config');

        function toggleNeighborConfig() {
            if (!neighborConfig) return;
            if (neighborEnabled && neighborEnabled.checked) {
                neighborConfig.classList.remove('ok-hidden');
            } else {
                neighborConfig.classList.add('ok-hidden');
            }
        }
        if (neighborEnabled) {
            neighborEnabled.addEventListener('change', toggleNeighborConfig);
            toggleNeighborConfig();
        }

        // ── Cron command builder ──────────────────────────────────────────────

        function updateCronCmd() {
            var el = document.getElementById('ar-cron-cmd');
            if (!el) return;
            var phpPath = el.dataset.php || '';
            var sched   = strVal('ar-cron-schedule', '0 2 * * *');
            var limit   = numVal('ar-cron-limit', 200);
            var force   = checked('ar-cron-force');
            var cats    = getMultitagIds('ar-cron-categories');
            var mfs     = getMultitagIds('ar-cron-manufacturers');

            var cmd = sched + ' php ' + phpPath + ' --limit=' + limit;
            if (force)       cmd += ' --force';
            if (cats.length) cmd += ' --category_id=' + cats.join(',');
            if (mfs.length)  cmd += ' --manufacturer_id=' + mfs.join(',');
            cmd += ' > /dev/null 2>&1';

            el.textContent = cmd;
        }

        ['ar-cron-schedule', 'ar-cron-limit', 'ar-cron-force'].forEach(function (id) {
            var node = document.getElementById(id);
            if (node) node.addEventListener('change', updateCronCmd);
            if (node && node.type === 'number') node.addEventListener('input', updateCronCmd);
        });

        updateCronCmd();

        // ── Multitag ──────────────────────────────────────────────────────────

        initMultitag('ar-attribute-tags',       'ar-attr-search',          'ar-attr-dropdown');
        initMultitag('ar-blacklist-products',  'ar-blacklist-prod-search', 'ar-blacklist-prod-dropdown');
        initMultitag('ar-blacklist-categories','ar-blacklist-cat-search',  'ar-blacklist-cat-dropdown');
        initMultitag('ar-gen-categories',    'ar-cat-search',    'ar-cat-dropdown');
        initMultitag('ar-gen-manufacturers', 'ar-mf-search',     'ar-mf-dropdown');
        initMultitag('ar-cron-categories',   'ar-cron-cat-search', 'ar-cron-cat-dropdown',  updateCronCmd);
        initMultitag('ar-cron-manufacturers','ar-cron-mf-search',  'ar-cron-mf-dropdown',   updateCronCmd);

        // ── Save ──────────────────────────────────────────────────────────────

        var saveBtn = document.getElementById('ar-btn-save');
        if (saveBtn) {
            saveBtn.addEventListener('click', function () {
                saveBtn.disabled = true;

                var fd     = new FormData();
                var prefix = 'module_oc_kit_auto_related_';

                fd.append(prefix + 'status',           checked('ar-status') ? 1 : 0);
                fd.append(prefix + 'related_limit',    numVal('ar-related-limit', 8));
                fd.append(prefix + 'overwrite',        checked('ar-overwrite') ? 1 : 0);
                fd.append(prefix + 'on_visit',         checked('ar-on-visit') ? 1 : 0);
                fd.append(prefix + 'exclude_oos',      checked('ar-exclude-oos') ? 1 : 0);
                fd.append(prefix + 'exclude_disabled', checked('ar-exclude-disabled') ? 1 : 0);
                fd.append(prefix + 'cache',            checked('ar-cache') ? 1 : 0);
                fd.append(prefix + 'cache_ttl',        numVal('ar-cache-ttl', 72));
                fd.append(prefix + 'candidate_limit',  numVal('ar-candidate-limit', 1000));

                var visitMode = document.querySelector('input[name="ar-visit-mode"]:checked');
                fd.append(prefix + 'visit_mode', visitMode ? visitMode.value : 'async');

                signals.forEach(function (sig) {
                    fd.append(prefix + 'weight_' + sig, numVal('ar-wn-' + sig, 0));
                });

                fd.append(prefix + 'neighbor_enabled', checked('ar-neighbor-enabled') ? 1 : 0);
                fd.append(prefix + 'neighbor_range',   numVal('ar-neighbor-range', 50));

                document.querySelectorAll('.ar-field-check:checked').forEach(function (cb) {
                    fd.append(prefix + 'field_list[]', cb.value);
                });
                fd.append(prefix + 'field_separator', strVal('ar-field-sep', ','));

                getMultitagIds('ar-attribute-tags').forEach(function (id) {
                    fd.append(prefix + 'attribute_ids[]', id);
                });
                fd.append(prefix + 'attribute_min_match', numVal('ar-attr-min-match', 1));

                fd.append(prefix + 'coorders_days', numVal('ar-coorders-days', 365));
                fd.append(prefix + 'coorders_min',  numVal('ar-coorders-min', 2));

                var statusSel = document.getElementById('ar-coorders-statuses');
                if (statusSel) {
                    Array.from(statusSel.options).forEach(function (opt) {
                        if (opt.selected) fd.append(prefix + 'coorders_statuses[]', opt.value);
                    });
                }

                // Price range signal
                fd.append(prefix + 'price_range_pct', numVal('ar-price-range-pct', 20));

                // Result sort & only_special
                fd.append(prefix + 'result_sort', strVal('ar-result-sort', 'score'));
                fd.append(prefix + 'only_special', checked('ar-only-special') ? 1 : 0);

                // Brand priority
                fd.append(prefix + 'brand_priority', checked('ar-brand-priority') ? 1 : 0);

                // Blacklist
                getMultitagIds('ar-blacklist-products').forEach(function (id) {
                    fd.append(prefix + 'blacklist_products[]', id);
                });
                getMultitagIds('ar-blacklist-categories').forEach(function (id) {
                    fd.append(prefix + 'blacklist_categories[]', id);
                });

                fetch(cfg.saveUrl, { method: 'POST', body: fd })
                    .then(function (r) { return r.json(); })
                    .then(function (json) {
                        if (json.success) {
                            window.okNotify(json.success, 'success');
                        } else {
                            window.okNotify(json.error || i18n.error_permission, 'error');
                        }
                    })
                    .catch(function () {
                        window.okNotify(i18n.error_permission || 'Error', 'error');
                    })
                    .finally(function () {
                        saveBtn.disabled = false;
                    });
            });
        }

        // ── Batch Generate ────────────────────────────────────────────────────

        var generateBtn  = document.getElementById('ar-btn-generate');
        var stopBtn      = document.getElementById('ar-btn-stop');
        var progressWrap = document.getElementById('ar-gen-progress-wrap');
        var progressBar  = document.getElementById('ar-gen-progress-bar');
        var statusText   = document.getElementById('ar-gen-status');
        var isGenerating = false;
        var stopRequested = false;

        if (generateBtn) {
            generateBtn.addEventListener('click', function () {
                if (isGenerating) return;
                isGenerating  = true;
                stopRequested = false;

                generateBtn.classList.add('ok-hidden');
                if (stopBtn)      stopBtn.classList.remove('ok-hidden');
                if (progressWrap) progressWrap.classList.remove('ok-hidden');
                if (progressBar)  progressBar.style.width = '0%';
                if (statusText)   statusText.textContent = i18n.text_generating || 'Generating…';

                runBatch(0);
            });
        }

        if (stopBtn) {
            stopBtn.addEventListener('click', function () { stopRequested = true; });
        }

        function runBatch(offset) {
            if (stopRequested) { finishGeneration(); return; }

            var fd = new FormData();
            fd.append('batch_size', 20);
            fd.append('offset', offset);
            fd.append('id_from',   strVal('ar-gen-id-from', ''));
            fd.append('id_to',     strVal('ar-gen-id-to',   ''));
            fd.append('overwrite', checked('ar-gen-overwrite') ? 1 : 0);
            getMultitagIds('ar-gen-categories').forEach(function (id) { fd.append('categories[]', id); });
            getMultitagIds('ar-gen-manufacturers').forEach(function (id) { fd.append('manufacturers[]', id); });

            fetch(cfg.generateUrl, { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (json) {
                    var total     = json.total || 0;
                    var processed = offset + (json.processed || 0);
                    var pct       = total > 0 ? Math.round(processed / total * 100) : 100;

                    if (progressBar) progressBar.style.width = pct + '%';
                    if (statusText) {
                        statusText.textContent = (i18n.text_processed || 'Processed') + ': ' +
                            processed + ' ' + (i18n.text_of || 'of') + ' ' + total;
                    }

                    if (json.done || stopRequested) {
                        finishGeneration(true);
                    } else {
                        runBatch(offset + (json.processed || 20));
                    }
                })
                .catch(function () {
                    window.okNotify(i18n.error_permission || 'Error', 'error');
                    finishGeneration();
                });
        }

        function finishGeneration(success) {
            isGenerating  = false;
            stopRequested = false;
            if (generateBtn) generateBtn.classList.remove('ok-hidden');
            if (stopBtn)     stopBtn.classList.add('ok-hidden');
            if (success && statusText) statusText.textContent = i18n.text_done || 'Done!';
        }

        // ── Stats ─────────────────────────────────────────────────────────────

        function loadStats() {
            fetch(cfg.statsUrl)
                .then(function (r) { return r.json(); })
                .then(function (json) {
                    var s = json.summary || {};
                    setText('ar-stat-total',    s.total        || 0);
                    setText('ar-stat-with',     s.with_related || 0);
                    setText('ar-stat-with-sub', (s.coverage    || 0) + '%');
                    setText('ar-stat-coverage', (s.coverage    || 0) + '%');
                    setText('ar-stat-without',  s.without      || 0);
                    setText('ar-stat-without-sub', s.total > 0 ? Math.round((s.without || 0) / s.total * 100) + '%' : '');

                    var tbody = document.getElementById('ar-stats-recent');
                    if (tbody && json.recent) {
                        tbody.innerHTML = '';
                        if (!json.recent.length) {
                            tbody.innerHTML = '<tr><td colspan="4" class="ok-text-center ok-muted">—</td></tr>';
                        } else {
                            json.recent.forEach(function (row) {
                                var tr = document.createElement('tr');
                                tr.innerHTML =
                                    '<td>' + esc(row.product_name || '#' + row.product_id) + '</td>' +
                                    '<td>' + (row.count || 0) + '</td>' +
                                    '<td><span class="ok-badge ok-badge-info-soft">' + esc(srcLbl[row.source] || row.source) + '</span></td>' +
                                    '<td>' + esc(row.generated_at || '') + '</td>';
                                tbody.appendChild(tr);
                            });
                        }
                    }
                })
                .catch(function () {});
        }

        // ── Preview ───────────────────────────────────────────────────────────

        var previewInput  = document.getElementById('ar-preview-search');
        var previewDrop   = document.getElementById('ar-preview-dropdown');
        var previewHidden = document.getElementById('ar-preview-product-id');
        var previewBtn    = document.getElementById('ar-btn-preview');
        var previewResult = document.getElementById('ar-preview-result');
        var previewTbody  = document.getElementById('ar-preview-tbody');
        var previewTimer  = null;

        if (previewInput && previewDrop) {
            previewInput.addEventListener('input', function () {
                clearTimeout(previewTimer);
                if (previewHidden) previewHidden.value = '';
                var term = previewInput.value.trim();
                if (term.length < 2) { closeDropdown(previewDrop); return; }

                previewTimer = setTimeout(function () {
                    fetch(cfg.autocompleteProductUrl + '&term=' + encodeURIComponent(term))
                        .then(function (r) { return r.json(); })
                        .then(function (results) {
                            previewDrop.innerHTML = '';
                            if (!results || !results.length) {
                                var em = document.createElement('div');
                                em.className   = 'ok-multitag-empty';
                                em.textContent = i18n.text_no_results || '—';
                                previewDrop.appendChild(em);
                            } else {
                                results.forEach(function (item) {
                                    var opt = document.createElement('div');
                                    opt.className   = 'ok-multitag-option';
                                    opt.textContent = item.label || (item.name + ' (#' + item.id + ')');
                                    opt.addEventListener('mousedown', function (e) {
                                        e.preventDefault();
                                        previewInput.value = opt.textContent;
                                        if (previewHidden) previewHidden.value = item.id;
                                        closeDropdown(previewDrop);
                                        previewInput.focus();
                                    });
                                    previewDrop.appendChild(opt);
                                });
                            }
                            previewDrop.classList.add('open');
                            positionDropdown(previewDrop);
                            activeDropdown = previewDrop;
                        })
                        .catch(function () { closeDropdown(previewDrop); });
                }, 250);
            });

            previewInput.addEventListener('blur', function () {
                setTimeout(function () { closeDropdown(previewDrop); }, 150);
            });
        }

        if (previewBtn) {
            previewBtn.addEventListener('click', function () {
                var productId = previewHidden ? previewHidden.value : '';
                if (!productId) {
                    window.okNotify(i18n.text_preview_product || 'Select a product first', 'warning');
                    return;
                }
                previewBtn.disabled = true;
                fetch(cfg.previewUrl + '&product_id=' + encodeURIComponent(productId))
                    .then(function (r) { return r.json(); })
                    .then(function (json) {
                        if (!previewTbody || !previewResult) return;
                        previewResult.classList.remove('ok-hidden');
                        previewTbody.innerHTML = '';
                        var results = json.results || [];
                        if (!results.length) {
                            previewTbody.innerHTML = '<tr><td colspan="2" class="ok-text-center ok-muted">' +
                                esc(i18n.text_preview_empty || '—') + '</td></tr>';
                        } else {
                            results.forEach(function (row, idx) {
                                var score = Math.round((row.score || 0) * 1000) / 10;
                                var tr = document.createElement('tr');
                                tr.innerHTML =
                                    '<td><span class="ok-badge ok-badge-info-soft">' + (idx + 1) + '</span> ' +
                                    esc(row.name || ('#' + row.product_id)) + '</td>' +
                                    '<td><strong>' + score + '%</strong></td>';
                                previewTbody.appendChild(tr);
                            });
                        }
                    })
                    .catch(function () {
                        window.okNotify(i18n.error_permission || 'Error', 'error');
                    })
                    .finally(function () { previewBtn.disabled = false; });
            });
        }

        // ── Preset scenarios ──────────────────────────────────────────────────

        document.querySelectorAll('.ar-preset').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var preset = {};
                try { preset = JSON.parse(btn.dataset.preset || '{}'); } catch (e) {}
                Object.keys(preset).forEach(function (sig) {
                    var range = document.getElementById('ar-w-' + sig);
                    var num   = document.getElementById('ar-wn-' + sig);
                    if (range) range.value = preset[sig];
                    if (num)   num.value   = preset[sig];
                });
            });
        });

        // ── Rule Builder (Constructor) ────────────────────────────────────────

        var ruleForm      = document.getElementById('ar-rule-form');
        var ruleFormTitle = document.getElementById('ar-rule-form-title');
        var rulesTbody    = document.getElementById('ar-rules-tbody');

        // ── Condition labels map ──────────────────────────────────────────────

        var condLabels = {
            // source
            category:           i18n.cond_src_category         || 'Category',
            manufacturer:       i18n.cond_src_manufacturer      || 'Brand',
            attribute:          i18n.cond_src_attribute         || 'Attribute value',
            name_contains:      i18n.cond_src_name_contains     || 'Name contains',
            // target (overrides where different label)
            same_category:      i18n.cond_tgt_same_category     || 'Same category',
            same_manufacturer:  i18n.cond_tgt_same_manufacturer || 'Same brand',
            dynamic_attribute:  i18n.cond_tgt_dynamic_attribute || 'Same attribute',
            price_range:        i18n.cond_tgt_price_range       || 'Price range ±%',
            only_special:       i18n.cond_tgt_only_special      || 'On sale only',
            exclude_oos:        i18n.cond_tgt_exclude_oos       || 'In stock only',
            brand_priority:     i18n.cond_tgt_brand_priority    || 'Same brand first'
        };

        // Simple flag conditions that need no extra input
        var flagConditions = { same_category: 1, same_manufacturer: 1, only_special: 1, exclude_oos: 1, brand_priority: 1 };

        // ── ConditionBuilder module ───────────────────────────────────────────

        var condRowCounter = 0;

        function addConditionRow(containerId, side, type, data) {
            var container = document.getElementById(containerId);
            if (!container) return;
            if (!type) return;

            data = data || {};
            condRowCounter++;
            var uid = 'cond-' + condRowCounter;

            var row = document.createElement('div');
            row.className = 'ar-cond-row';
            row.dataset.type = type;

            var label = condLabels[type] || type;
            var html = '<span class="ar-cond-type-badge">' + esc(label) + '</span>';

            if (flagConditions[type]) {
                // Flag conditions: show hint if available, no extra input
                var helpKey = 'text_cond_' + type.replace(/_/g, '') + '_help';
                // Map known help keys
                var helpMap = {
                    same_category:     i18n.text_cond_same_cat_help,
                    same_manufacturer: i18n.text_cond_same_mf_help,
                    only_special:      i18n.text_cond_only_special_help,
                    exclude_oos:       i18n.text_cond_exclude_oos_help,
                    brand_priority:    i18n.text_cond_brand_priority_help
                };
                var hint = helpMap[type] || '';
                if (hint) html += '<span class="ar-cond-hint">' + esc(hint) + '</span>';

            } else if (type === 'category') {
                html += buildMultitagHtml(uid, cfg.autocompleteCat, data.ids || []);

            } else if (type === 'manufacturer') {
                html += buildMultitagHtml(uid, cfg.autocompleteMf, data.ids || []);

            } else if (type === 'attribute') {
                // attribute_id autocomplete + value text input
                html += buildAttrAutocompleteHtml(uid, data.attribute_id || '', data.attribute_name || '');
                html += ' <span class="ar-cond-eq">=</span> ';
                html += '<input type="text" class="ok-input ok-w-180 ar-cond-attr-value" placeholder="' +
                    esc(i18n.entry_cond_attribute_value || 'Value') + '" value="' + esc(data.value || '') + '">';

            } else if (type === 'dynamic_attribute') {
                // attribute_id autocomplete only — matches same value as source product
                html += buildAttrAutocompleteHtml(uid, data.attribute_id || '', data.attribute_name || '');
                var hint2 = i18n.text_cond_dyn_attr_help || '';
                if (hint2) html += '<span class="ar-cond-hint">' + esc(hint2) + '</span>';

            } else if (type === 'name_contains') {
                html += '<input type="text" class="ok-input ok-w-240 ar-cond-name-text" placeholder="' +
                    esc(i18n.entry_cond_name_text || 'Text') + '" value="' + esc(data.text || '') + '">';

            } else if (type === 'price_range') {
                html += '<input type="number" class="ok-input ok-w-80 ar-cond-price-pct" min="1" max="500" ' +
                    'placeholder="' + esc(i18n.entry_cond_price_pct || '±%') + '" value="' + esc(data.pct || 20) + '">';
                html += '<span class="ar-cond-hint ok-ml-1">%</span>';
            }

            html += '<button type="button" class="ar-cond-remove ok-btn ok-btn-xs ok-btn-muted" title="Remove">×</button>';

            row.innerHTML = html;
            container.appendChild(row);

            // Initialize multitag inside newly created row
            if (type === 'category' || type === 'manufacturer') {
                var mtWrap = row.querySelector('.ok-multitag[data-uid="' + uid + '"]');
                var mtInput = row.querySelector('.ar-mt-input[data-uid="' + uid + '"]');
                var mtDrop  = row.querySelector('.ar-mt-drop[data-uid="' + uid + '"]');
                if (mtWrap && mtInput && mtDrop) {
                    var url = type === 'manufacturer' ? cfg.autocompleteMf : cfg.autocompleteCat;
                    mtWrap.dataset.url = url;
                    initCondMultitag(mtWrap, mtInput, mtDrop);
                    // Restore saved tags
                    if (Array.isArray(data.ids)) {
                        data.ids.forEach(function (item) {
                            addTag(mtWrap, mtInput, item.id, item.label || ('#' + item.id));
                        });
                    }
                }
            }

            if (type === 'attribute' || type === 'dynamic_attribute') {
                var atInput = row.querySelector('.ar-cond-attr-input[data-uid="' + uid + '"]');
                var atDrop  = row.querySelector('.ar-cond-attr-drop[data-uid="' + uid + '"]');
                var atHidden = row.querySelector('.ar-cond-attr-id[data-uid="' + uid + '"]');
                if (atInput && atDrop) {
                    initAttrAutocomplete(atInput, atDrop, atHidden);
                }
            }
        }

        function buildMultitagHtml(uid, url, savedIds) {
            return '<span class="ok-multitag" data-uid="' + uid + '" data-url="' + esc(url) + '" style="display:inline-flex;vertical-align:middle;">' +
                '<span class="ok-multitag-inner">' +
                '<input type="text" class="ok-multitag-input ar-mt-input" data-uid="' + uid + '" ' +
                'placeholder="' + esc(i18n.entry_cond_ids_placeholder || 'Search…') + '">' +
                '</span>' +
                '<div class="ok-multitag-dropdown ar-mt-drop" data-uid="' + uid + '"></div>' +
                '</span>';
        }

        function buildAttrAutocompleteHtml(uid, attrId, attrName) {
            return '<span class="ar-cond-attr-wrap" style="position:relative;display:inline-flex;align-items:center;gap:4px;">' +
                '<input type="hidden" class="ar-cond-attr-id" data-uid="' + uid + '" value="' + esc(attrId) + '">' +
                '<input type="text" class="ok-input ok-w-180 ar-cond-attr-input" data-uid="' + uid + '" ' +
                'placeholder="' + esc(i18n.entry_cond_attribute_id || 'Attribute') + '" value="' + esc(attrName) + '">' +
                '<div class="ok-multitag-dropdown ar-cond-attr-drop" data-uid="' + uid + '"></div>' +
                '</span>';
        }

        function initCondMultitag(wrap, input, drop) {
            var url   = wrap.dataset.url || '';
            var timer = null;

            input.addEventListener('input', function () {
                clearTimeout(timer);
                var term = input.value.trim();
                if (term.length < 2) { closeDropdown(drop); return; }

                timer = setTimeout(function () {
                    fetch(url + '&term=' + encodeURIComponent(term))
                        .then(function (r) { return r.json(); })
                        .then(function (results) { renderDropdown(drop, wrap, input, results); })
                        .catch(function () { closeDropdown(drop); });
                }, 250);
            });

            input.addEventListener('blur', function () {
                setTimeout(function () { closeDropdown(drop); }, 150);
            });
        }

        function initAttrAutocomplete(input, drop, hiddenInput) {
            var timer = null;

            input.addEventListener('input', function () {
                clearTimeout(timer);
                if (hiddenInput) hiddenInput.value = '';
                var term = input.value.trim();
                if (term.length < 2) { closeDropdown(drop); return; }

                timer = setTimeout(function () {
                    fetch(cfg.autocompleteAttr + '&term=' + encodeURIComponent(term))
                        .then(function (r) { return r.json(); })
                        .then(function (results) {
                            drop.innerHTML = '';
                            if (!results || !results.length) {
                                var em = document.createElement('div');
                                em.className   = 'ok-multitag-empty';
                                em.textContent = i18n.text_no_results || '—';
                                drop.appendChild(em);
                            } else {
                                results.forEach(function (item) {
                                    var opt = document.createElement('div');
                                    opt.className   = 'ok-multitag-option';
                                    opt.textContent = item.label || item.name;
                                    opt.addEventListener('mousedown', function (e) {
                                        e.preventDefault();
                                        input.value = opt.textContent;
                                        if (hiddenInput) hiddenInput.value = item.id;
                                        closeDropdown(drop);
                                        input.focus();
                                    });
                                    drop.appendChild(opt);
                                });
                            }
                            drop.classList.add('open');
                            positionDropdown(drop);
                            activeDropdown = drop;
                        })
                        .catch(function () { closeDropdown(drop); });
                }, 250);
            });

            input.addEventListener('blur', function () {
                setTimeout(function () { closeDropdown(drop); }, 150);
            });
        }

        function collectConditions(containerId) {
            var container = document.getElementById(containerId);
            if (!container) return [];
            var result = [];

            container.querySelectorAll('.ar-cond-row').forEach(function (row) {
                var type = row.dataset.type;
                if (!type) return;

                var cond = { type: type };

                if (flagConditions[type]) {
                    // No extra data

                } else if (type === 'category' || type === 'manufacturer') {
                    var mtWrap = row.querySelector('.ok-multitag');
                    var ids = [];
                    if (mtWrap) {
                        mtWrap.querySelectorAll('.ok-multitag-tag').forEach(function (tag) {
                            var input = mtWrap.querySelector('.ok-multitag-input');
                            ids.push({ id: parseInt(tag.dataset.id, 10), label: tag.textContent.replace('×', '').trim() });
                        });
                    }
                    cond.ids = ids;

                } else if (type === 'attribute') {
                    var atHidden = row.querySelector('.ar-cond-attr-id');
                    var atInput  = row.querySelector('.ar-cond-attr-input');
                    var valInput = row.querySelector('.ar-cond-attr-value');
                    cond.attribute_id   = atHidden ? (parseInt(atHidden.value, 10) || 0) : 0;
                    cond.attribute_name = atInput  ? atInput.value.trim() : '';
                    cond.value          = valInput ? valInput.value.trim() : '';
                    if (!cond.attribute_id || !cond.value) return; // skip incomplete

                } else if (type === 'dynamic_attribute') {
                    var atHidden2 = row.querySelector('.ar-cond-attr-id');
                    var atInput2  = row.querySelector('.ar-cond-attr-input');
                    cond.attribute_id   = atHidden2 ? (parseInt(atHidden2.value, 10) || 0) : 0;
                    cond.attribute_name = atInput2  ? atInput2.value.trim() : '';
                    if (!cond.attribute_id) return; // skip incomplete

                } else if (type === 'name_contains') {
                    var textInput = row.querySelector('.ar-cond-name-text');
                    cond.text = textInput ? textInput.value.trim() : '';
                    if (!cond.text) return; // skip empty

                } else if (type === 'price_range') {
                    var pctInput = row.querySelector('.ar-cond-price-pct');
                    cond.pct = pctInput ? (parseInt(pctInput.value, 10) || 20) : 20;
                }

                result.push(cond);
            });

            return result;
        }

        function clearConditions(containerId) {
            var container = document.getElementById(containerId);
            if (container) container.innerHTML = '';
        }

        function restoreConditions(containerId, side, conditions) {
            clearConditions(containerId);
            if (!Array.isArray(conditions)) return;
            conditions.forEach(function (cond) {
                addConditionRow(containerId, side, cond.type, cond);
            });
        }

        // Summary text for rules table (e.g. "2 conditions" or first condition label)
        function condSummary(conditions) {
            if (!Array.isArray(conditions) || !conditions.length) return '<span class="ok-muted">—</span>';
            var labels = conditions.map(function (c) {
                return '<span class="ok-badge ok-badge-info-soft ok-badge-xs">' + esc(condLabels[c.type] || c.type) + '</span>';
            });
            return labels.join(' ');
        }

        // ── Add condition selects ─────────────────────────────────────────────

        var addSrcSelect = document.getElementById('ar-add-src-type');
        var addTgtSelect = document.getElementById('ar-add-tgt-type');

        if (addSrcSelect) {
            addSrcSelect.addEventListener('change', function () {
                var type = addSrcSelect.value;
                if (!type) return;
                addConditionRow('ar-src-conds', 'source', type, {});
                addSrcSelect.value = '';
                if (window.lucide) lucide.createIcons();
            });
        }

        if (addTgtSelect) {
            addTgtSelect.addEventListener('change', function () {
                var type = addTgtSelect.value;
                if (!type) return;
                addConditionRow('ar-tgt-conds', 'target', type, {});
                addTgtSelect.value = '';
                if (window.lucide) lucide.createIcons();
            });
        }

        // Delegate: remove condition row
        document.addEventListener('click', function (e) {
            var removeBtn = e.target.closest('.ar-cond-remove');
            if (removeBtn) {
                var row = removeBtn.closest('.ar-cond-row');
                if (row) row.remove();
            }
        });

        // ── Rule list: add / edit / delete ───────────────────────────────────

        var addRuleBtn = document.getElementById('ar-btn-add-rule');
        if (addRuleBtn) {
            addRuleBtn.addEventListener('click', function () {
                openRuleForm(null);
            });
        }

        var saveRuleBtn = document.getElementById('ar-btn-save-rule');
        if (saveRuleBtn) {
            saveRuleBtn.addEventListener('click', function () {
                var srcConds = collectConditions('ar-src-conds');
                var tgtConds = collectConditions('ar-tgt-conds');

                var fd = new FormData();
                fd.append('rule_id',           strVal('ar-rule-id', '0'));
                fd.append('name',              strVal('ar-rule-name', ''));
                fd.append('status',            checked('ar-rule-status') ? 1 : 0);
                fd.append('sort_order',        numVal('ar-rule-sort-order', 0));
                // Multilingual block_title: collect all language inputs
                document.querySelectorAll('.ar-rule-block-title-input').forEach(function (inp) {
                    fd.append('block_title[' + inp.dataset.langId + ']', inp.value);
                });
                fd.append('result_limit',      numVal('ar-rule-result-limit', 8));
                fd.append('result_sort',       strVal('ar-rule-result-sort', 'random'));
                fd.append('source_conditions', JSON.stringify(srcConds));
                fd.append('target_conditions', JSON.stringify(tgtConds));

                saveRuleBtn.disabled = true;
                fetch(cfg.saveRuleUrl, { method: 'POST', body: fd })
                    .then(function (r) { return r.json(); })
                    .then(function (json) {
                        if (json.success) {
                            window.okNotify(json.success, 'success');
                            closeRuleForm();
                            loadRules();
                        } else {
                            window.okNotify(json.error || 'Error', 'error');
                        }
                    })
                    .catch(function () { window.okNotify(i18n.error_permission || 'Error', 'error'); })
                    .finally(function () { saveRuleBtn.disabled = false; });
            });
        }

        var cancelRuleBtn = document.getElementById('ar-btn-cancel-rule');
        if (cancelRuleBtn) {
            cancelRuleBtn.addEventListener('click', function () { closeRuleForm(); });
        }

        // Delegate: edit / delete rule rows
        document.addEventListener('click', function (e) {
            var editBtn = e.target.closest('.ar-rule-edit');
            if (editBtn) {
                var row = editBtn.closest('tr');
                if (row) {
                    try { openRuleForm(JSON.parse(row.dataset.rule || 'null')); } catch (ex) {}
                }
                return;
            }
            var delBtn = e.target.closest('.ar-rule-delete');
            if (delBtn) {
                if (!window.confirm(i18n.confirm_delete_rule || 'Delete?')) return;
                var ruleId = delBtn.dataset.id;
                var fd = new FormData();
                fd.append('rule_id', ruleId);
                fetch(cfg.deleteRuleUrl, { method: 'POST', body: fd })
                    .then(function (r) { return r.json(); })
                    .then(function (json) {
                        if (json.success) { window.okNotify(json.success, 'success'); loadRules(); }
                        else { window.okNotify(json.error || 'Error', 'error'); }
                    })
                    .catch(function () {});
            }
        });

        function loadRules() {
            if (!cfg.listRulesUrl || !rulesTbody) return;
            fetch(cfg.listRulesUrl)
                .then(function (r) { return r.json(); })
                .then(function (json) { renderRules(json.rules || []); })
                .catch(function () {});
        }

        function renderRules(rules) {
            if (!rulesTbody) return;
            rulesTbody.innerHTML = '';
            if (!rules.length) {
                rulesTbody.innerHTML = '<tr><td colspan="5" class="ok-text-center ok-muted">' +
                    esc(i18n.text_no_rules || '—') + '</td></tr>';
                return;
            }
            rules.forEach(function (rule) {
                var tr = document.createElement('tr');
                tr.dataset.rule = JSON.stringify(rule);
                var statusBadge = rule.status == 1
                    ? '<span class="ok-badge ok-badge-success-soft"><i data-lucide="check" style="width:12px;height:12px;"></i> ON</span>'
                    : '<span class="ok-badge ok-badge-muted">OFF</span>';
                var srcConds = Array.isArray(rule.source_conditions) ? rule.source_conditions : [];
                var tgtConds = Array.isArray(rule.target_conditions) ? rule.target_conditions : [];
                tr.innerHTML =
                    '<td>' + esc(rule.sort_order) + '</td>' +
                    '<td><strong>' + esc(rule.name) + '</strong></td>' +
                    '<td class="ok-text-sm">' + condSummary(srcConds) + '</td>' +
                    '<td class="ok-text-sm">' + condSummary(tgtConds) + '</td>' +
                    '<td>' + statusBadge + '</td>' +
                    '<td class="ok-flex ok-gap-4">' +
                        '<button type="button" class="ok-btn ok-btn-default ok-btn-xs ar-rule-edit">' +
                            esc(i18n.button_edit_rule || 'Edit') + '</button>' +
                        '<button type="button" class="ok-btn ok-btn-danger ok-btn-xs ar-rule-delete" data-id="' + rule.rule_id + '">' +
                            esc(i18n.button_delete_rule || 'Delete') + '</button>' +
                    '</td>';
                rulesTbody.appendChild(tr);
            });
            if (window.lucide) lucide.createIcons();
        }

        function openRuleForm(rule) {
            if (!ruleForm) return;
            ruleForm.classList.remove('ok-hidden');

            var isEdit = rule && rule.rule_id > 0;
            if (ruleFormTitle) {
                ruleFormTitle.innerHTML = '<i data-lucide="edit-3"></i> ' +
                    esc(isEdit ? (i18n.button_edit_rule || 'Edit') : (i18n.button_add_rule || 'Add'));
            }

            var hiddenId = document.getElementById('ar-rule-id');
            if (hiddenId) hiddenId.value = isEdit ? rule.rule_id : '0';

            var nameEl = document.getElementById('ar-rule-name');
            if (nameEl) nameEl.value = isEdit ? (rule.name || '') : '';

            var statusEl = document.getElementById('ar-rule-status');
            if (statusEl) statusEl.checked = !isEdit || rule.status == 1;

            var sortEl = document.getElementById('ar-rule-sort-order');
            if (sortEl) sortEl.value = isEdit ? (rule.sort_order || 0) : 0;

            // Restore multilingual block_title
            var blockTitles = (isEdit && rule.block_title && typeof rule.block_title === 'object')
                ? rule.block_title : {};
            document.querySelectorAll('.ar-rule-block-title-input').forEach(function (inp) {
                inp.value = blockTitles[inp.dataset.langId] || '';
            });

            var limitEl = document.getElementById('ar-rule-result-limit');
            if (limitEl) limitEl.value = isEdit ? (rule.result_limit || 8) : 8;

            var sortSelEl = document.getElementById('ar-rule-result-sort');
            if (sortSelEl) sortSelEl.value = isEdit ? (rule.result_sort || 'random') : 'random';

            // Restore constructor conditions
            restoreConditions('ar-src-conds', 'source', isEdit ? (rule.source_conditions || []) : []);
            restoreConditions('ar-tgt-conds', 'target', isEdit ? (rule.target_conditions || []) : []);

            if (window.lucide) lucide.createIcons();
            ruleForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function closeRuleForm() {
            if (ruleForm) ruleForm.classList.add('ok-hidden');
        }

    });

    // ── Multitag ──────────────────────────────────────────────────────────────

    var activeDropdown = null; // currently open dropdown element

    // Click outside any multitag closes the open dropdown
    document.addEventListener('click', function (e) {
        if (!activeDropdown) return;
        var wrap = activeDropdown.closest('.ok-multitag');
        if (wrap && !wrap.contains(e.target)) closeDropdown(activeDropdown);
    });

    // Click on ok-multitag-inner focuses the input inside
    document.addEventListener('click', function (e) {
        var inner = e.target.closest('.ok-multitag-inner');
        if (!inner) return;
        var input = inner.querySelector('.ok-multitag-input');
        if (input) input.focus();
    });

    // Remove tag on × click (event delegation, works for all multitags)
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.ok-multitag-tag-remove');
        if (!btn) return;
        var tag  = btn.closest('.ok-multitag-tag');
        var wrap = tag && tag.closest('.ok-multitag');
        if (tag) tag.remove();
        if (wrap && wrap._onChange) wrap._onChange();
    });

    function initMultitag(containerId, inputId, dropdownId, onChange) {
        var wrap  = document.getElementById(containerId);
        var input = document.getElementById(inputId);
        var drop  = document.getElementById(dropdownId);
        if (!wrap || !input || !drop) return;

        var url   = wrap.dataset.url || '';
        var timer = null;

        wrap._onChange = onChange || null;

        input.addEventListener('input', function () {
            clearTimeout(timer);
            var term = input.value.trim();
            if (term.length < 2) { closeDropdown(drop); return; }

            timer = setTimeout(function () {
                fetch(url + '&term=' + encodeURIComponent(term))
                    .then(function (r) { return r.json(); })
                    .then(function (results) { renderDropdown(drop, wrap, input, results); })
                    .catch(function () { closeDropdown(drop); });
            }, 250);
        });

        input.addEventListener('blur', function () {
            setTimeout(function () { closeDropdown(drop); }, 150);
        });
    }

    function renderDropdown(drop, wrap, input, results) {
        drop.innerHTML = '';

        if (!results || results.length === 0) {
            var em = document.createElement('div');
            em.className = 'ok-multitag-empty';
            em.textContent = (window.arI18n && window.arI18n.text_no_results) || '—';
            drop.appendChild(em);
        } else {
            results.forEach(function (item) {
                var opt = document.createElement('div');
                opt.className   = 'ok-multitag-option';
                opt.textContent = item.label || (item.name + ' (#' + item.id + ')');
                opt.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    addTag(wrap, input, item.id, opt.textContent);
                    input.value = '';
                    closeDropdown(drop);
                    input.focus();
                    if (wrap._onChange) wrap._onChange();
                });
                drop.appendChild(opt);
            });
        }

        drop.classList.add('open');
        positionDropdown(drop);
        activeDropdown = drop;
    }

    function closeDropdown(drop) {
        if (!drop) return;
        drop.innerHTML = '';
        drop.classList.remove('open');
        drop.classList.remove('drop-up');
        if (activeDropdown === drop) activeDropdown = null;
    }

    // Flip the dropdown above its trigger when it would otherwise overflow the
    // viewport bottom. Uses the trigger's bounding rect (parent of the dropdown
    // wrapper) rather than the dropdown itself, since the dropdown's own rect
    // depends on which side it ends up on — circular.
    function positionDropdown(drop) {
        if (!drop) return;
        var dropH   = drop.offsetHeight || 220;
        var parent  = drop.parentElement;
        var rect    = parent ? parent.getBoundingClientRect() : drop.getBoundingClientRect();
        var spaceBelow = window.innerHeight - rect.bottom;
        var spaceAbove = rect.top;
        if (spaceBelow < dropH && spaceAbove > spaceBelow) {
            drop.classList.add('drop-up');
        } else {
            drop.classList.remove('drop-up');
        }
    }

    function addTag(wrap, input, id, label) {
        var inner = wrap.querySelector('.ok-multitag-inner');
        if (!inner) return;

        // Avoid duplicates
        if (inner.querySelector('.ok-multitag-tag[data-id="' + id + '"]')) return;

        var tag = document.createElement('span');
        tag.className  = 'ok-multitag-tag';
        tag.dataset.id = id;
        tag.innerHTML  = esc(label) + '<span class="ok-multitag-tag-remove" data-id="' + id + '">×</span>';
        inner.insertBefore(tag, input);
    }

    function getMultitagIds(containerId) {
        var wrap = document.getElementById(containerId);
        if (!wrap) return [];
        return Array.from(wrap.querySelectorAll('.ok-multitag-tag')).map(function (t) { return t.dataset.id; });
    }

    // ── Tiny helpers ──────────────────────────────────────────────────────────

    function el(id)               { return document.getElementById(id); }
    function checked(id)          { var n = el(id); return n && n.checked; }
    function numVal(id, fallback) { var n = el(id); return n ? (parseInt(n.value, 10) || fallback) : fallback; }
    function strVal(id, fallback) { var n = el(id); return n ? n.value : fallback; }
    function setText(id, val)     { var n = el(id); if (n) n.textContent = val; }
    function esc(str)             { return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

}());
