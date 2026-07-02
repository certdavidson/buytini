// Content Blocks Pro Admin | © 2026 oc-kit.com | https://oc-kit.com
(function () {
    'use strict';

    // ── Globals set by Twig ───────────────────────────────────────────────────
    var t        = window.cbI18n        || {};  // editor i18n
    var ts       = window.cbSettingsI18n|| {};  // settings i18n
    var urls     = window.cbSettingsUrls|| {};  // settings URLs
    var cbTypes  = window.cbTypes       || {};
    var cbLangs  = window.cbLanguages   || [];
    var cbLangId = window.cbLanguageId  || 1;

    var cbImgIdCounter = 0; // unique IDs for OC3 image pickers

    // ─────────────────────────────────────────────────────────────────────────
    // AJAX helpers
    // ─────────────────────────────────────────────────────────────────────────

    function post(url, data, done, fail) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', url, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4) return;
            if (xhr.status === 200) {
                try { done(JSON.parse(xhr.responseText)); }
                catch (e) { if (fail) fail('Parse error'); }
            } else {
                if (fail) fail('HTTP ' + xhr.status);
            }
        };
        var parts = [];
        function encode(k, v) {
            parts.push(encodeURIComponent(k) + '=' + encodeURIComponent(v == null ? '' : v));
        }
        function flatten(obj, prefix) {
            Object.keys(obj).forEach(function (k) {
                var key = prefix ? prefix + '[' + k + ']' : k;
                var val = obj[k];
                if (val !== null && typeof val === 'object' && !Array.isArray(val)) {
                    flatten(val, key);
                } else if (Array.isArray(val)) {
                    val.forEach(function (item, i) {
                        if (typeof item === 'object') {
                            flatten(item, key + '[' + i + ']');
                        } else {
                            encode(key + '[]', item);
                        }
                    });
                } else {
                    encode(key, val);
                }
            });
        }
        flatten(data, '');
        xhr.send(parts.join('&'));
    }

    function get(url, params, done, fail) {
        var qs = Object.keys(params).map(function (k) {
            return encodeURIComponent(k) + '=' + encodeURIComponent(params[k]);
        }).join('&');
        var xhr = new XMLHttpRequest();
        xhr.open('GET', url + (qs ? (url.indexOf('?') >= 0 ? '&' : '?') + qs : ''), true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4) return;
            if (xhr.status === 200) {
                try { done(JSON.parse(xhr.responseText)); }
                catch (e) { if (fail) fail(); }
            } else {
                if (fail) fail();
            }
        };
        xhr.send();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DOM helpers
    // ─────────────────────────────────────────────────────────────────────────

    function qs(sel, ctx) { return (ctx || document).querySelector(sel); }
    function qsa(sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); }

    function show(el) { if (el) el.removeAttribute('hidden'); }
    function hide(el) { if (el) el.setAttribute('hidden', ''); }

    function insertHTML(container, html) {
        var tmp = document.createElement('div');
        tmp.innerHTML = html;
        while (tmp.firstChild) container.appendChild(tmp.firstChild);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Tabs (sidebar) — settings page
    // ─────────────────────────────────────────────────────────────────────────

    function initTabs() {
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.ok-tabs-sidebar-item');
            if (!btn) return;
            var tabId = btn.dataset.tab;
            if (!tabId) return;
            var sidebar = btn.closest('.ok-tabs-sidebar');
            if (!sidebar) return;
            qsa('.ok-tabs-sidebar-item', sidebar).forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            var layout = btn.closest('.ok-layout');
            if (layout) {
                qsa('.ok-layout-panel', layout).forEach(function (p) { p.classList.remove('active'); });
                var panel = qs('#' + tabId, layout);
                if (panel) panel.classList.add('active');
            }
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Settings page: Save
    // ─────────────────────────────────────────────────────────────────────────

    function initSettingsSave() {
        var btn = qs('#cb-btn-save');
        if (!btn) return;

        btn.addEventListener('click', function () {
            var data = {};

            var statusEl = qs('#cb-status');
            data.status = statusEl && statusEl.checked ? 1 : 0;

            var wysiwyg = qs('[name="wysiwyg_editor"]');
            if (wysiwyg) data.wysiwyg_editor = wysiwyg.value;

            var openai = qs('[name="openai_key"]');
            if (openai) data.openai_key = openai.value;

            // license_key is NOT collected — setting_save reads it from config and
            // re-injects it into editSetting, so it survives independent of POST.

            var blogType = qs('[name="blog_type"]');
            if (blogType) data.blog_type = blogType.value;

            var uploadDir = qs('[name="upload_dir"]');
            if (uploadDir) data.upload_dir = uploadDir.value;

            var enableCacheEl = qs('[name="enable_cache"][type="checkbox"]');
            data.enable_cache = enableCacheEl && enableCacheEl.checked ? 1 : 0;

            var customCss = qs('[name="custom_css"]');
            if (customCss) data.custom_css = customCss.value;

            var customJs = qs('[name="custom_js"]');
            if (customJs) data.custom_js = customJs.value;

            // Types config (handles 2-level and 3-level nesting for device_inputs)
            data.types = {};
            qsa('[name^="types["]').forEach(function (el) {
                var val = el.type === 'checkbox' ? (el.checked ? 1 : 0) : el.value;
                var m3 = el.name.match(/^types\[([^\]]+)\]\[([^\]]+)\]\[([^\]]+)\]$/);
                var m2 = !m3 && el.name.match(/^types\[([^\]]+)\]\[([^\]]+)\]$/);
                if (m3) {
                    var tp = m3[1], f = m3[2], s = m3[3];
                    if (!data.types[tp]) data.types[tp] = {};
                    if (!data.types[tp][f]) data.types[tp][f] = {};
                    data.types[tp][f][s] = val;
                } else if (m2) {
                    var tp = m2[1], f = m2[2];
                    if (!data.types[tp]) data.types[tp] = {};
                    data.types[tp][f] = val;
                }
            });

            btn.disabled = true;
            post(urls.save, data, function (json) {
                btn.disabled = false;
                if (json.error) {
                    window.okNotify(json.error, 'error');
                } else {
                    window.okNotify(json.success || ts.text_success, 'success');
                }
            }, function (err) {
                btn.disabled = false;
                window.okNotify(err || ts.error_permission, 'error');
            });
        });

        // Type params toggle (expand/collapse per-type settings)
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.cb-type-toggle')) return;
            var block = e.target.closest('.cb-type-settings');
            var params = block && qs('.cb-type-params', block);
            if (!params) return;
            if (params.hasAttribute('hidden')) {
                params.removeAttribute('hidden');
            } else {
                params.setAttribute('hidden', '');
            }
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Settings page: Stickers
    // ─────────────────────────────────────────────────────────────────────────

    function initStickers() {
        var addBtn = qs('#cb-btn-add-sticker');
        if (!addBtn) return;

        // Per-row sticker-language pill switcher (each sticker row has its own pills
        // sitting above its cb-sticker-inputs; click switches that row only).
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.cb-sticker-lang-tabs .ok-tabs-pills-item');
            if (!btn) return;
            var tabs = btn.closest('.cb-sticker-lang-tabs');
            var row  = btn.closest('.cb-sticker-row, .cb-sticker-text-col') || btn.closest('td');
            if (!tabs || !row) return;
            var lid = btn.dataset.stickerLang;
            qsa('.ok-tabs-pills-item', tabs).forEach(function (b) {
                b.classList.toggle('active', b === btn);
            });
            qsa('.cb-sticker-lang-row', row).forEach(function (r) {
                r.classList.toggle('hidden', r.dataset.stickerLang !== lid);
            });
        });

        // Apply initial preview styles + text from the first language input
        qsa('.cb-sticker-row').forEach(function (row) {
            updateStickerPreview(row);
        });

        // Sortable for stickers
        var stickersBody = qs('#cb-stickers-body');
        if (stickersBody && window.Sortable) {
            new window.Sortable(stickersBody, {
                handle: '.ok-handle',
                draggable: '.cb-sticker-row',
                animation: 150,
                onEnd: function () {
                    qsa('.cb-sticker-row', stickersBody).forEach(function (row, idx) {
                        var id = parseInt(row.dataset.id || 0, 10);
                        if (id > 0) {
                            post(urls.stickers, {
                                action: 'save',
                                sticker_id: id,
                                color:  (qs('.cb-sticker-color',  row) || {}).value || '',
                                bg_color: (qs('.cb-sticker-bg',   row) || {}).value || '',
                                border_color: (qs('.cb-sticker-border', row) || {}).value || '',
                                border_radius: (qs('.cb-sticker-radius', row) || {}).value || '',
                                position: (qs('.cb-sticker-pos-thumb', row) || {dataset:{}}).dataset.position || 'top-left',
                                status: ((qs('.cb-sticker-status', row) || {}).checked ? 1 : 0),
                                sort_order: idx,
                                'text': {}
                            }, function () {});
                        }
                    });
                }
            });
        }

        // Position button click
        document.addEventListener('click', function (e) {
            var posBtn = e.target.closest('.cb-sticker-pos-btn');
            if (!posBtn) return;
            var thumb = posBtn.closest('.cb-sticker-pos-thumb');
            if (!thumb) return;
            qsa('.cb-sticker-pos-btn', thumb).forEach(function (b) { b.classList.remove('active'); });
            posBtn.classList.add('active');
            thumb.dataset.position = posBtn.dataset.pos;
            var row = posBtn.closest('.cb-sticker-row');
            if (row) saveSticker(row);
        });

        addBtn.addEventListener('click', function () {
            var tpl = qs('#cb-sticker-tpl');
            if (!tpl) return;
            var clone = document.importNode(tpl.content, true);
            // fix toggle label for uniqueness
            var uid = Date.now();
            var cb  = clone.querySelector('.cb-sticker-status');
            var lbl = clone.querySelector('label[for]');
            if (cb && lbl) { cb.id = 'sticker-new-' + uid; lbl.setAttribute('for', cb.id); }
            qs('#cb-stickers-body').appendChild(clone);
            // Render lucide icons inside the freshly inserted row
            if (window.lucide) lucide.createIcons();
            // Save immediately so a DB ID is assigned (per-row pill switcher works
            // out of the box because each row carries its own .cb-sticker-lang-tabs)
            var newRow = qs('#cb-stickers-body').lastElementChild;
            if (newRow) { updateStickerPreview(newRow); saveSticker(newRow); }
        });

        document.addEventListener('click', function (e) {
            // Delete sticker
            if (e.target.closest('.cb-btn-delete-sticker')) {
                var row = e.target.closest('.cb-sticker-row');
                if (!row) return;
                var id = parseInt(row.dataset.id || 0, 10);
                if (id > 0) {
                    post(urls.stickers, { action: 'delete', sticker_id: id }, function (json) {
                        if (json.success) row.remove();
                        else window.okNotify(json.error, 'error');
                    });
                } else {
                    row.remove();
                }
            }

            // Save sticker on blur — we save all on main save? No, stickers have own AJAX.
            // Actually save each sticker row automatically when user leaves it.
        });

        // Auto-save sticker row on input blur / change
        document.addEventListener('change', function (e) {
            var row = e.target.closest('.cb-sticker-row');
            if (!row) return;
            saveSticker(row);
        });

        // Live preview update on typing (color, bg, border, text)
        document.addEventListener('input', function (e) {
            var row = e.target.closest('.cb-sticker-row');
            if (!row) return;
            var inp = e.target;
            if (!inp.classList.contains('cb-sticker-color') &&
                !inp.classList.contains('cb-sticker-bg') &&
                !inp.classList.contains('cb-sticker-border') &&
                !inp.classList.contains('cb-sticker-radius') &&
                !inp.classList.contains('cb-sticker-text')) return;
            updateStickerPreview(row);
        });
    }

    function updateStickerPreview(row) {
        var preview = qs('.cb-sticker-preview', row);
        if (!preview) return;
        var color    = (qs('.cb-sticker-color',  row) || {}).value || '';
        var bg       = (qs('.cb-sticker-bg',     row) || {}).value || '';
        var border   = (qs('.cb-sticker-border', row) || {}).value || '';
        var radius   = (qs('.cb-sticker-radius', row) || {}).value || '';
        var firstTxt = qs('.cb-sticker-text', row);
        if (color)  preview.style.color        = color;
        if (bg)     preview.style.background   = bg;
        if (border) preview.style.borderColor  = border;
        preview.style.borderWidth  = border ? '1px' : '0';
        preview.style.borderStyle  = border ? 'solid' : '';
        preview.style.borderRadius = radius ? radius + 'px' : '';
        if (firstTxt) preview.textContent = firstTxt.value || (t.preview_sticker_label || 'Sticker');
    }

    function saveSticker(row) {
        var id       = parseInt(row.dataset.id || 0, 10);
        var color    = (qs('.cb-sticker-color',  row) || {}).value || '';
        var bg       = (qs('.cb-sticker-bg',     row) || {}).value || '';
        var border   = (qs('.cb-sticker-border', row) || {}).value || '';
        var radius   = (qs('.cb-sticker-radius', row) || {}).value || '';
        var status   = qs('.cb-sticker-status', row);
        var posThumb = qs('.cb-sticker-pos-thumb', row);
        var position = posThumb ? (posThumb.dataset.position || 'top-left') : 'top-left';
        var texts    = {};

        updateStickerPreview(row);

        qsa('.cb-sticker-text', row).forEach(function (inp) {
            var langId = inp.dataset.langId;
            if (langId) texts[langId] = inp.value;
        });

        var data = {
            action:       'save',
            sticker_id:   id,
            color:        color,
            bg_color:     bg,
            border_color: border,
            border_radius: radius,
            position:     position,
            status:       status && status.checked ? 1 : 0,
            'text':       texts
        };

        post(urls.stickers, data, function (json) {
            if (json.sticker_id) row.dataset.id = json.sticker_id;
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Settings page: Presets
    // ─────────────────────────────────────────────────────────────────────────

    function initPresets() {
        var addBtn = qs('#cb-btn-add-preset');
        if (!addBtn) return;

        // Sortable for presets table
        var presetsBody = qs('#cb-presets-body');
        if (presetsBody && window.Sortable) {
            new window.Sortable(presetsBody, {
                handle: '.ok-handle',
                draggable: '.cb-preset-row',
                animation: 150,
                onEnd: function () {
                    var rows = qsa('.cb-preset-row', presetsBody);
                    rows.forEach(function (row, idx) {
                        var id = parseInt(row.dataset.id || 0, 10);
                        if (id > 0) {
                            post(urls.presets, { action: 'save',
                                preset_id: id,
                                name:    (qs('.cb-preset-name',    row) || {}).value || '',
                                classes: (qs('.cb-preset-classes', row) || {}).value || '',
                                group:   (qs('.cb-preset-group',   row) || {}).value || '',
                                sort_order: idx
                            }, function () {});
                        }
                    });
                }
            });
        }

        addBtn.addEventListener('click', function () {
            var tpl = qs('#cb-preset-tpl');
            if (!tpl) return;
            qs('#cb-presets-body').appendChild(document.importNode(tpl.content, true));
        });

        // Reset presets to defaults
        var resetBtn = qs('#cb-btn-reset-presets');
        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                if (!confirm(ts.text_reset_presets_confirm || 'Скинути до стандартних?')) return;
                post(urls.presets, { action: 'reset' }, function (json) {
                    if (json.success) {
                        window.location.reload();
                    }
                });
            });
        }

        document.addEventListener('click', function (e) {
            if (e.target.closest('.cb-btn-delete-preset')) {
                var row = e.target.closest('.cb-preset-row');
                if (!row) return;
                var id = parseInt(row.dataset.id || 0, 10);
                if (id > 0) {
                    post(urls.presets, { action: 'delete', preset_id: id }, function (json) {
                        if (json.success) row.remove();
                        else window.okNotify(json.error, 'error');
                    });
                } else {
                    row.remove();
                }
            }
        });

        document.addEventListener('change', function (e) {
            var row = e.target.closest('.cb-preset-row');
            if (!row) return;
            savePreset(row);
        });
    }

    function savePreset(row) {
        var id      = parseInt(row.dataset.id || 0, 10);
        var name    = (qs('.cb-preset-name',    row) || {}).value || '';
        var classes = (qs('.cb-preset-classes', row) || {}).value || '';
        var group   = (qs('.cb-preset-group',   row) || {}).value || '';
        if (!name) return;

        post(urls.presets, { action: 'save', preset_id: id, name: name, classes: classes, group: group }, function (json) {
            if (json.preset_id) row.dataset.id = json.preset_id;
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Settings page: Migration
    // ─────────────────────────────────────────────────────────────────────────

    function initMigration() {
        var btn = qs('#cb-btn-migrate');
        if (!btn) return;

        btn.addEventListener('click', function () {
            if (!confirm(ts.text_migration_warning || 'Продовжити міграцію?')) return;
            btn.disabled = true;
            btn.textContent = ts.text_migrating || 'Міграція...';

            post(urls.migrate, {}, function (json) {
                btn.disabled = false;
                btn.textContent = ts.button_migrate || 'Мігрувати';
                if (json.error) {
                    window.okNotify(json.error, 'error');
                } else {
                    window.okNotify(json.success, 'success');
                }
            }, function () {
                btn.disabled = false;
                window.okNotify('Error', 'error');
            });
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Settings page: Demo page
    // ─────────────────────────────────────────────────────────────────────────

    function initDemoPage() {
        var btnCreate = qs('#cb-btn-demo-create');
        var btnDelete = qs('#cb-btn-demo-delete');
        if (!btnCreate && !btnDelete) return;

        if (btnCreate) {
            btnCreate.addEventListener('click', function () {
                btnCreate.disabled = true;
                post(urls.demo, { action: 'create' }, function (json) {
                    btnCreate.disabled = false;
                    if (json.error) { window.okNotify(json.error, 'error'); return; }
                    window.okNotify(json.success, 'success');
                    if (json.view_url) {
                        setTimeout(function () { window.open(json.view_url, '_blank'); }, 600);
                    }
                }, function () {
                    btnCreate.disabled = false;
                    window.okNotify(t.error_generic || 'Error', 'error');
                });
            });
        }
        if (btnDelete) {
            btnDelete.addEventListener('click', function () {
                if (!confirm(t.text_demo_delete_confirm || 'Delete demo page and all its blocks?')) return;
                btnDelete.disabled = true;
                post(urls.demo, { action: 'delete' }, function (json) {
                    btnDelete.disabled = false;
                    if (json.error) { window.okNotify(json.error, 'error'); return; }
                    window.okNotify(json.success, 'success');
                }, function () {
                    btnDelete.disabled = false;
                    window.okNotify(t.error_generic || 'Error', 'error');
                });
            });
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Settings page: Form submissions list
    // ─────────────────────────────────────────────────────────────────────────

    function initSubmissions() {
        var body  = qs('#cb-subm-body');
        var total = qs('#cb-subm-total');
        var pages = qs('#cb-subm-pages');
        var filter = qs('.cb-subm-filter-block');
        var reload = qs('#cb-btn-subm-reload') || qs('#cb-subm-reload');
        var modal  = qs('#cb-modal-submission');
        var modalBody = qs('#cb-subm-modal-body');
        var modalId   = qs('#cb-subm-modal-id');
        if (!body) return;

        var page = 1;

        function load() {
            post(urls.submissions, { action: 'list', block_id: filter ? (filter.value || 0) : 0, page: page }, function (json) {
                if (json.error) { window.okNotify(json.error, 'error'); return; }
                renderRows(json.submissions || []);
                if (total) total.textContent = (json.total || 0);
                renderPages(json.pages || 1);
            });
        }

        function renderRows(rows) {
            body.innerHTML = '';
            if (!rows.length) {
                body.innerHTML = '<tr><td colspan="6" class="ok-text-muted ok-text-center ok-py-3">—</td></tr>';
                return;
            }
            rows.forEach(function (r) {
                var tr = document.createElement('tr');
                tr.innerHTML = '<td>#' + r.submission_id + '</td>' +
                    '<td>' + r.block_id + '</td>' +
                    '<td>' + (r.page_route || '—') + (r.page_id ? ' #' + r.page_id : '') + '</td>' +
                    '<td><code class="ok-text-sm">' + (r.ip || '—') + '</code></td>' +
                    '<td class="ok-text-sm">' + (r.date_added || '') + '</td>' +
                    '<td class="ok-text-right">' +
                      '<button type="button" class="ok-btn ok-btn-default ok-btn-xs cb-btn-subm-view" data-id="' + r.submission_id + '"><i data-lucide="eye"></i></button> ' +
                      '<button type="button" class="ok-btn ok-btn-danger ok-btn-xs cb-btn-subm-del"  data-id="' + r.submission_id + '"><i data-lucide="trash-2"></i></button>' +
                    '</td>';
                body.appendChild(tr);
            });
            initLucide(body);
        }

        function renderPages(n) {
            if (!pages) return;
            pages.innerHTML = '';
            if (n < 2) return;
            for (var i = 1; i <= n; i++) {
                var b = document.createElement('button');
                b.type = 'button';
                b.className = 'ok-btn ok-btn-default ok-btn-xs cb-subm-page' + (i === page ? ' active' : '');
                b.dataset.page = i;
                b.textContent = i;
                pages.appendChild(b);
            }
        }

        function openView(id) {
            modalBody.innerHTML = '<div class="ok-text-muted ok-py-3">…</div>';
            modalId.textContent = '#' + id;
            show(modal);
            // GET request — submission_id in querystring
            get(urls.submissions, { action: 'view', submission_id: id }, function (json) {
                if (json.error || !json.submission) {
                    modalBody.innerHTML = '<div class="ok-text-danger">' + (json.error || 'Error') + '</div>';
                    return;
                }
                var s = json.submission;
                var html = '<div class="ok-text-sm ok-text-muted ok-mb-3">' +
                    'Block: <b>' + s.block_id + '</b> · ' +
                    'Page: <b>' + (s.page_route || '—') + (s.page_id ? ' #' + s.page_id : '') + '</b> · ' +
                    'IP: <code>' + (s.ip || '—') + '</code> · ' +
                    s.date_added + '</div>';
                html += '<table class="ok-table cb-subm-detail-table"><tbody>';
                (s.fields || []).forEach(function (f) {
                    var v;
                    if (f.file_path) {
                        v = '<a href="' + f.download_url + '" target="_blank" rel="noopener"><i data-lucide="download"></i> ' + f.field_value + '</a>' +
                            '<div class="ok-text-muted ok-text-sm">' + f.file_path + '</div>';
                    } else {
                        v = '<div style="white-space:pre-wrap">' + escapeHtml(f.field_value || '—') + '</div>';
                    }
                    html += '<tr><th style="width:30%">' + f.field_name + '</th><td>' + v + '</td></tr>';
                });
                html += '</tbody></table>';
                modalBody.innerHTML = html;
                initLucide(modalBody);
            });
        }

        function escapeHtml(s) {
            return String(s).replace(/[&<>"']/g, function (c) {
                return ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' })[c];
            });
        }

        // Wire events
        if (reload) reload.addEventListener('click', load);
        if (filter) filter.addEventListener('change', function () { page = 1; load(); });

        document.addEventListener('click', function (e) {
            var view = e.target.closest('.cb-btn-subm-view');
            if (view) { openView(parseInt(view.dataset.id, 10)); return; }

            var del = e.target.closest('.cb-btn-subm-del');
            if (del) {
                if (!confirm('Delete submission?')) return;
                post(urls.submissions, { action: 'delete', submission_id: parseInt(del.dataset.id, 10) }, function (json) {
                    if (json.error) { window.okNotify(json.error, 'error'); return; }
                    load();
                });
                return;
            }

            var p = e.target.closest('.cb-subm-page');
            if (p) { page = parseInt(p.dataset.page, 10) || 1; load(); return; }

            if (e.target.closest('[data-dismiss="cb-modal-submission"]') ||
                (e.target.classList && e.target.classList.contains('cb-modal-backdrop') && e.target.id === 'cb-modal-submission')) {
                hide(modal);
            }
        });

        // Auto-load when user opens the submissions tab
        var btn = qs('[data-tab="tab-submissions"]');
        if (btn) btn.addEventListener('click', load);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Editor: init (called when #cb-editor is present)
    // ─────────────────────────────────────────────────────────────────────────

    var editorEl = null;
    var editorUrls = {};
    var templateSavingBlockEl = null;
    var translateBlockEl = null;
    var settingsTarget = null; // { el, type: 'block'|'row'|'col'|'element' }

    function initEditor() {
        editorEl = qs('#cb-editor');
        if (!editorEl) return;

        editorUrls = {
            save:        editorEl.dataset.saveUrl,
            block:       editorEl.dataset.blockUrl,
            element:     editorEl.dataset.elementUrl,
            duplicate:   editorEl.dataset.duplicateUrl,
            translate:   editorEl.dataset.translateUrl,
            templates:    editorEl.dataset.templatesUrl,
            video:        editorEl.dataset.videoUrl,
            autocomplete: editorEl.dataset.autocompleteUrl,
            upload:       editorEl.dataset.uploadUrl
        };

        initEditorEvents();
        initSortable();
        initWysiwyg();
        initToolbarFilters();
        initFormBuilder();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Toolbar filters — show/hide blocks by selected type(s) and block_id(s).
    // Both multiselects apply with AND between filters, OR within each.
    // ─────────────────────────────────────────────────────────────────────────

    function initToolbarFilters() {
        var typeSel = qs('.cb-filter-type', editorEl);
        if (!typeSel) return;

        typeSel.addEventListener('change', function () {
            var type = typeSel.value;
            typeSel.classList.toggle('has-selection', type !== '');
            qsa('.cb-block', qs('#cb-blocks-list')).forEach(function (b) {
                b.style.display = (type === '' || b.dataset.blockType === type) ? '' : 'none';
            });
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Editor: event delegation
    // ─────────────────────────────────────────────────────────────────────────

    function initEditorEvents() {
        var list = qs('#cb-blocks-list');

        // Add block dropdown toggle
        document.addEventListener('click', function (e) {
            var addBtn = e.target.closest('#cb-btn-add-block');
            if (addBtn) {
                openModal('cb-modal-add-block');
                return;
            }

            // Close el dropdowns on outside click
            if (!e.target.closest('.cb-add-el-wrap')) {
                qsa('.cb-el-dropdown').forEach(hide);
            }

            // Choose block type from modal
            if (e.target.closest('.cb-type-card')) {
                var card = e.target.closest('.cb-type-card');
                var blockType = card.dataset.blockType;
                closeModal('cb-modal-add-block');
                addBlock(blockType);
                return;
            }

            // From template
            if (e.target.closest('#cb-btn-from-template')) {
                openTemplatesModal();
                return;
            }

            // Save blocks
            if (e.target.closest('#cb-btn-save-blocks')) {
                saveBlocks();
                return;
            }

            // Collapse/expand all blocks
            if (e.target.closest('#cb-btn-collapse-all')) {
                var blocks = qsa('.cb-block', editorEl);
                // If any block is expanded, collapse them all; otherwise expand all.
                var anyExpanded = false;
                blocks.forEach(function (b) { if (!b.classList.contains('cb-block--collapsed')) anyExpanded = true; });
                blocks.forEach(function (b) {
                    b.classList.toggle('cb-block--collapsed', anyExpanded);
                    var ic = qs('.cb-btn-collapse i', b);
                    if (ic) {
                        ic.setAttribute('data-lucide', anyExpanded ? 'chevron-down' : 'chevron-up');
                        if (window.lucide) window.lucide.createIcons({ nodes: [ic] });
                    }
                });
                return;
            }

            // Delete block
            if (e.target.closest('.cb-btn-delete-block')) {
                var blockEl = e.target.closest('.cb-block');
                if (blockEl) deleteBlock(blockEl);
                return;
            }

            // Collapse block
            if (e.target.closest('.cb-btn-collapse')) {
                var bodyEl = e.target.closest('.cb-block');
                if (bodyEl) bodyEl.classList.toggle('cb-block--collapsed');
                var icon = e.target.closest('.cb-btn-collapse').querySelector('i');
                if (icon) { var ic = bodyEl.classList.contains('cb-block--collapsed') ? 'chevron-down' : 'chevron-up'; icon.setAttribute('data-lucide', ic); if (window.lucide) window.lucide.createIcons({ nodes: [icon] }); }
                return;
            }

            // Block theme select
            if (e.target.closest('.cb-btn-select-theme')) {
                var themeBtn = e.target.closest('.cb-btn-select-theme');
                var themeBlock = themeBtn.closest('.cb-block');
                if (themeBlock) openThemeModal(themeBlock);
                return;
            }

            // Tag button group (text element)
            if (e.target.closest('.cb-el-tag-btn')) {
                var tagBtn = e.target.closest('.cb-el-tag-btn');
                var tagWrap = tagBtn.closest('.cb-el-tag-btns');
                var tagVal  = tagBtn.closest('.cb-element-header') && tagBtn.closest('.cb-element-header').querySelector('.cb-el-tag-val');
                qsa('.cb-el-tag-btn', tagWrap).forEach(function (b) { b.classList.remove('active'); });
                tagBtn.classList.add('active');
                if (tagVal) tagVal.value = tagBtn.dataset.tag;
                return;
            }

            // Theme card click in theme modal
            if (e.target.closest('.cb-theme-card')) {
                var themeCard = e.target.closest('.cb-theme-card');
                var themeVal  = themeCard.dataset.theme;
                var targetBlock = qs('#cb-modal-themes').dataset.targetBlock;
                var bl2 = targetBlock ? qs('.cb-block[data-block-id="' + targetBlock + '"]') : null;
                if (bl2 && themeVal) {
                    var themeInput = qs('.cb-block-theme', bl2);
                    var themeLabel = qs('.cb-block-theme-label', bl2);
                    if (themeInput) themeInput.value = themeVal;
                    if (themeLabel) themeLabel.textContent = themeVal;
                    qsa('.cb-theme-card', qs('#cb-themes-grid')).forEach(function (c) { c.classList.remove('active'); });
                    themeCard.classList.add('active');
                }
                closeModal('cb-modal-themes');
                return;
            }

            // Param toggle button
            if (e.target.closest('.cb-param-btn')) {
                e.target.closest('.cb-param-btn').classList.toggle('active');
                return;
            }

            // Block settings
            if (e.target.closest('.cb-block-params-toggle')) {
                var paramsEl = e.target.closest('.cb-block-params');
                if (paramsEl) {
                    paramsEl.classList.toggle('cb-block-params--expanded');
                    var ico = paramsEl.querySelector('.cb-block-params-toggle i');
                    if (ico && window.lucide) window.lucide.createIcons({ nodes: [ico] });
                }
                return;
            }

            if (e.target.closest('.cb-btn-block-settings')) {
                var bl = e.target.closest('.cb-block');
                if (bl) openSettings(bl, 'block');
                return;
            }

            // Duplicate block
            if (e.target.closest('.cb-btn-duplicate')) {
                var bl2 = e.target.closest('.cb-block');
                if (bl2) duplicateBlock(bl2);
                return;
            }

            // Translate block
            if (e.target.closest('.cb-btn-translate')) {
                translateBlockEl = e.target.closest('.cb-block');
                openModal('cb-modal-translate');
                return;
            }

            // Device visibility toggle
            if (e.target.closest('.cb-device-btn')) {
                e.target.closest('.cb-device-btn').classList.toggle('active');
                return;
            }

            // Copy shortcode (click on text)
            if (e.target.closest('.cb-shortcode-text')) {
                var scBtn = e.target.closest('.cb-shortcode-text');
                var shortcode = scBtn.dataset.shortcode || scBtn.textContent.trim();
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(shortcode).then(function () {
                        window.okNotify(t.text_shortcode_copied || 'Скопійовано!', 'success');
                    });
                } else {
                    var ta = document.createElement('textarea');
                    ta.value = shortcode;
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand('copy');
                    document.body.removeChild(ta);
                    window.okNotify(t.text_shortcode_copied || 'Скопійовано!', 'success');
                }
                return;
            }

            // Save as template
            if (e.target.closest('.cb-btn-save-template')) {
                templateSavingBlockEl = e.target.closest('.cb-block');
                openModal('cb-modal-save-template');
                return;
            }

            // Add row
            if (e.target.closest('.cb-btn-add-row')) {
                var blockEl2 = e.target.closest('.cb-block');
                if (blockEl2) addRow(blockEl2);
                return;
            }

            // Delete row
            if (e.target.closest('.cb-btn-delete-row')) {
                var rowEl = e.target.closest('.cb-row');
                if (rowEl) rowEl.remove();
                return;
            }

            // Row settings
            if (e.target.closest('.cb-btn-row-settings')) {
                var rowEl2 = e.target.closest('.cb-row');
                if (rowEl2) openSettings(rowEl2, 'row');
                return;
            }

            // Add col (in row or accordion)
            if (e.target.closest('.cb-btn-add-col')) {
                var btn2 = e.target.closest('.cb-btn-add-col');
                var rowEl3 = btn2.closest('.cb-row');
                var blockEl3 = btn2.closest('.cb-block');
                if (rowEl3) addCol(rowEl3);
                else if (blockEl3) addAccordionCol(blockEl3);
                return;
            }

            // Delete col
            if (e.target.closest('.cb-btn-delete-col')) {
                var colEl = e.target.closest('.cb-col');
                if (colEl) colEl.remove();
                return;
            }

            // Col settings
            if (e.target.closest('.cb-btn-col-settings')) {
                var colEl2 = e.target.closest('.cb-col');
                if (colEl2) openSettings(colEl2, 'col');
                return;
            }

            // Col width button group
            if (e.target.closest('.cb-col-width-btn')) {
                var wBtn = e.target.closest('.cb-col-width-btn');
                var wGroup = wBtn.closest('.cb-col-width-group');
                var colEl3 = wBtn.closest('.cb-col');
                var w = parseInt(wBtn.dataset.w, 10);
                if (wGroup) {
                    wGroup.dataset.current = w;
                    qsa('.cb-col-width-btn', wGroup).forEach(function (b) {
                        var bw = parseInt(b.dataset.w, 10);
                        b.classList.toggle('active', bw === w);
                        b.classList.toggle('lit', bw > 0 && bw < w);
                    });
                }
                if (colEl3) colEl3.dataset.width = w;
                return;
            }

            // Col width hover effects
            if (e.target.closest('.cb-col-width-group')) {
                // handled via CSS + mouseover/mouseout below
            }

            // Add element to col
            if (e.target.closest('.cb-btn-add-element-col')) {
                var btn3 = e.target.closest('.cb-btn-add-element-col');
                var dd2 = btn3.nextElementSibling;
                if (dd2 && dd2.classList.contains('cb-el-dropdown')) {
                    // Restrict elements for "video" block to only "video" type
                    var parentBlock = btn3.closest('.cb-block');
                    var blockType   = parentBlock ? parentBlock.dataset.blockType : '';
                    qsa('.cb-el-dropdown-item', dd2).forEach(function (it) {
                        if (blockType === 'video') {
                            it.classList.toggle('hidden', it.dataset.elType !== 'video');
                        } else {
                            it.classList.remove('hidden');
                        }
                    });
                    dd2.hidden ? show(dd2) : hide(dd2);
                }
                return;
            }

            // Add element dropdown item (in col)
            if (e.target.closest('.cb-el-dropdown-item')) {
                var ddItem = e.target.closest('.cb-el-dropdown-item');
                var elType = ddItem.dataset.elType;
                var colEl4 = ddItem.closest('.cb-col');
                hide(ddItem.closest('.cb-el-dropdown'));
                if (colEl4 && elType) addElementToCol(colEl4, elType);
                return;
            }

            // Add element (flat block)
            if (e.target.closest('.cb-btn-add-element')) {
                var btn4 = e.target.closest('.cb-btn-add-element');
                var elType2 = btn4.dataset.elType;
                var blockEl4 = btn4.closest('.cb-block');
                if (blockEl4 && elType2) addFlatElement(blockEl4, elType2);
                return;
            }

            // Delete element
            if (e.target.closest('.cb-btn-delete-el')) {
                var elEl = e.target.closest('.cb-element');
                if (elEl) elEl.remove();
                return;
            }

            // Element settings
            if (e.target.closest('.cb-btn-el-settings')) {
                var elEl2 = e.target.closest('.cb-element');
                if (elEl2) openSettings(elEl2, 'element');
                return;
            }

            // Lang tab switch
            if (e.target.closest('.cb-lang-tab')) {
                var tab = e.target.closest('.cb-lang-tab');
                var langId = tab.dataset.langId;
                var body = tab.closest('.cb-element-body, .cb-col-header');
                if (!body) return;
                qsa('.cb-lang-tab', body).forEach(function (t2) { t2.classList.remove('active'); });
                qsa('.cb-lang-panel', body).forEach(function (p) { p.classList.remove('active'); });
                tab.classList.add('active');
                var panel = qs('.cb-lang-panel[data-lang-id="' + langId + '"]', body);
                if (panel) panel.classList.add('active');
                // For accordion_col header lang
                qsa('.cb-acc-title', body).forEach(function (inp) {
                    inp.classList.toggle('hidden', inp.dataset.lang !== langId);
                });
                return;
            }


            // Video poster clear (tile)
            if (e.target.closest('.cb-btn-clear-video-poster')) {
                var posterTile = e.target.closest('.cb-video-tile');
                if (posterTile) {
                    var posterImg = qs('.cb-video-tile-thumb', posterTile);
                    var posterVal = qs('.cb-video-poster-val', posterTile);
                    var posterClr = qs('.cb-btn-clear-video-poster', posterTile);
                    if (posterImg) posterImg.src = posterImg.dataset.placeholder || '';
                    if (posterVal) posterVal.value = '';
                    if (posterClr) hide(posterClr);
                }
                return;
            }

            // Video auto-thumb clear (tile)
            if (e.target.closest('.cb-btn-clear-video-thumb')) {
                var thumbTile = e.target.closest('.cb-video-tile');
                if (thumbTile) {
                    var tImg = qs('.cb-video-tile-thumb', thumbTile);
                    var tVal = qs('.cb-video-thumb-val', thumbTile);
                    var tClr = qs('.cb-btn-clear-video-thumb', thumbTile);
                    if (tImg) tImg.src = tImg.dataset.placeholder || '';
                    if (tVal) tVal.value = '';
                    if (tClr) hide(tClr);
                }
                return;
            }

            // Video local clear (tile)
            if (e.target.closest('.cb-btn-clear-video-local')) {
                var localTile = e.target.closest('.cb-video-tile');
                if (localTile) {
                    var localImg = qs('.cb-video-tile-thumb', localTile);
                    var localVal = qs('.cb-video-local-val', localTile);
                    var localClr = qs('.cb-btn-clear-video-local', localTile);
                    if (localImg) localImg.src = localImg.dataset.placeholder || '';
                    if (localVal) localVal.value = '';
                    if (localClr) hide(localClr);
                }
                return;
            }

            // Upload image (label wraps file input, click triggers file dialog)
            // File input change is handled in the separate listener below.


            // Autocomplete pickers — legacy pick buttons (still used elsewhere)
            if (e.target.closest('.cb-btn-pick-product, .cb-btn-pick-category, .cb-btn-pick-article')) {
                var panel = e.target.closest('.cb-element').querySelector('.cb-autocomplete-panel');
                if (panel) { panel.hidden ? show(panel) : hide(panel); }
                return;
            }

            // Compact items (carousel_product / categories_item / blog_article_item):
            // click on the header thumb re-opens the autocomplete panel so a
            // different entity can be picked. The label may be an <a> linking to
            // the admin entity-edit page — let the link fire normally.
            var itemHeaderHit = e.target.closest('.cb-item-thumb, span.cb-item-label');
            if (itemHeaderHit) {
                var itemEl = itemHeaderHit.closest('.cb-element.cb-item-compact, .cb-element.cb-item');
                if (itemEl) {
                    var ap = qs('.cb-autocomplete-panel', itemEl);
                    if (ap) { ap.hidden ? show(ap) : hide(ap); }
                    return;
                }
            }

            // Product overrides toggle
            if (e.target.closest('.cb-btn-toggle-overrides')) {
                var ovrEl = e.target.closest('.cb-product-overrides');
                if (ovrEl) {
                    var body = qs('.cb-product-overrides-body', ovrEl);
                    if (body) { body.hidden ? show(body) : hide(body); }
                }
                return;
            }

            // Modal close
            if (e.target.closest('.cb-modal-close') || e.target.closest('[data-dismiss]')) {
                var modalId = (e.target.closest('[data-dismiss]') || {}).dataset.dismiss;
                if (modalId) closeModal(modalId);
                return;
            }

            // Close modal on backdrop click
            if (e.target.classList.contains('cb-modal-backdrop')) {
                closeModal(e.target.id);
                return;
            }

            // Modal settings apply
            if (e.target.closest('#cb-modal-settings-apply')) {
                applySettings();
                return;
            }

            // Save template confirm
            if (e.target.closest('#cb-btn-save-tpl-confirm')) {
                saveAsTemplate();
                return;
            }

            // Translate confirm
            if (e.target.closest('#cb-btn-translate-confirm')) {
                doTranslate();
                return;
            }
        });

        // Block status toggle
        document.addEventListener('change', function (e) {
            if (!e.target.classList.contains('cb-block-status')) return;
            var blockEl = e.target.closest('.cb-block');
            if (blockEl) blockEl.classList.toggle('cb-block--inactive', !e.target.checked);
        });

        // Image file input (upload button)
        document.addEventListener('change', function (e) {
            if (!e.target.classList.contains('cb-image-file-input')) return;
            var file  = e.target.files[0];
            var elEl  = e.target.closest('.cb-element');
            var pickRow = e.target.closest('.cb-image-row');
            if (file && elEl) uploadImage(elEl, file, pickRow);
            e.target.value = '';
        });

        // Image drag&drop
        document.addEventListener('dragover', function (e) {
            var pick = e.target.closest('.cb-image-row');
            if (!pick) return;
            e.preventDefault();
            pick.classList.add('cb-drag-over', 'ok-drag-over');
        });

        document.addEventListener('dragleave', function (e) {
            var pick = e.target.closest('.cb-image-row');
            if (!pick) return;
            if (!pick.contains(e.relatedTarget)) {
                pick.classList.remove('cb-drag-over', 'ok-drag-over');
            }
        });

        document.addEventListener('drop', function (e) {
            var pick = e.target.closest('.cb-image-row');
            if (!pick) return;
            e.preventDefault();
            pick.classList.remove('cb-drag-over', 'ok-drag-over');
            var file = e.dataTransfer.files && e.dataTransfer.files[0];
            var elEl = pick.closest('.cb-element');
            if (file && elEl) uploadImage(elEl, file, pick);
        });

        // Multi-upload zone (carousel_image block) — drag/drop or click to bulk-add elements
        document.addEventListener('dragover', function (e) {
            var zone = e.target.closest('.cb-multi-upload-zone');
            if (!zone) return;
            e.preventDefault();
            zone.classList.add('ok-drag-over');
        });
        document.addEventListener('dragleave', function (e) {
            var zone = e.target.closest('.cb-multi-upload-zone');
            if (!zone) return;
            if (!zone.contains(e.relatedTarget)) zone.classList.remove('ok-drag-over');
        });
        document.addEventListener('drop', function (e) {
            var zone = e.target.closest('.cb-multi-upload-zone');
            if (!zone) return;
            e.preventDefault();
            zone.classList.remove('ok-drag-over');
            handleMultiUpload(zone, e.dataTransfer.files);
        });
        document.addEventListener('change', function (e) {
            if (!e.target.classList.contains('cb-multi-upload-input')) return;
            var zone = e.target.closest('.cb-multi-upload-zone');
            if (zone) handleMultiUpload(zone, e.target.files);
            e.target.value = '';
        });

        // Col width button group — hover highlights (star-rating style)
        document.addEventListener('mouseover', function (e) {
            var btn = e.target.closest('.cb-col-width-btn');
            if (!btn) return;
            var group = btn.closest('.cb-col-width-group');
            if (!group) return;
            var hw = parseInt(btn.dataset.w, 10);
            qsa('.cb-col-width-btn', group).forEach(function (b) {
                var bw = parseInt(b.dataset.w, 10);
                b.classList.toggle('hover-lit', bw > 0 && bw <= hw);
            });
        });

        document.addEventListener('mouseout', function (e) {
            var btn = e.target.closest('.cb-col-width-btn');
            if (!btn) return;
            var group = btn.closest('.cb-col-width-group');
            if (!group) return;
            qsa('.cb-col-width-btn', group).forEach(function (b) {
                b.classList.remove('hover-lit');
            });
        });

        // Range ↔ number input sync (e.g. ms-font-size-range ↔ ms-font-size)
        document.addEventListener('input', function (e) {
            var id = e.target.id;
            if (id) {
                var isRange = e.target.type === 'range';
                var partnerId = isRange ? id.replace(/-range$/, '') : id + '-range';
                var partner = qs('#' + partnerId);
                if (partner && partner !== e.target && partner.type !== e.target.type) {
                    partner.value = e.target.value;
                }
            }
        });

        // Autocomplete input (regular cb-autocomplete-wrap + ok-multitag widgets)
        document.addEventListener('input', function (e) {
            var inp = e.target;
            if (!inp.classList.contains('cb-autocomplete-input')) return;
            var wrap = inp.closest('.cb-autocomplete-wrap');
            var multitag = inp.closest('.ok-multitag');
            var acType, dropdown;
            if (multitag) {
                acType   = multitag.dataset.acType || 'product';
                dropdown = qs('.cb-autocomplete-dropdown', multitag);
            } else if (wrap) {
                acType   = wrap.dataset.type || 'product';
                dropdown = qs('.cb-autocomplete-dropdown', wrap);
            } else {
                return;
            }
            var q = inp.value.trim();
            if (q.length < 2) { hide(dropdown); return; }
            fetchAutocompleteInto(acType, q, dropdown, function (item) {
                if (multitag) {
                    addMultitagTag(multitag, item);
                    inp.value = '';
                    hide(dropdown);
                } else if (wrap) {
                    selectAutocomplete(wrap, item);
                    hide(dropdown);
                }
            });
        });

        // Multitag remove
        document.addEventListener('click', function (e) {
            var rm = e.target.closest('.ok-multitag-remove');
            if (!rm) return;
            var tag = rm.closest('.ok-multitag-tag');
            if (tag) tag.remove();
        });

        // Video URL — auto-fetch YouTube thumbnail (debounced)
        var videoFetchTimers = new WeakMap();
        document.addEventListener('input', function (e) {
            var inp = e.target;
            if (!inp.classList.contains('cb-video-link')) return;
            var elEl = inp.closest('.cb-element');
            if (!elEl) return;
            clearTimeout(videoFetchTimers.get(inp));
            var url = inp.value.trim();
            if (!url || url.length < 10) return;
            videoFetchTimers.set(inp, setTimeout(function () {
                fetchVideoThumb(elEl);
            }, 600));
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Add block
    // ─────────────────────────────────────────────────────────────────────────

    function openThemeModal(blockEl) {
        var modal     = qs('#cb-modal-themes');
        var grid      = qs('#cb-themes-grid', modal);
        var blockId   = blockEl.dataset.blockId || '';
        var blockType = blockEl.dataset.blockType || '';
        var current   = (qs('.cb-block-theme', blockEl) || {}).value || 'default';
        var typeDef   = cbTypes[blockType] || {};
        var themes    = typeDef.themes || [{name: 'default'}];

        modal.dataset.targetBlock = blockId;
        grid.innerHTML = '';

        themes.forEach(function (theme) {
            var card = document.createElement('button');
            card.type = 'button';
            card.className = 'cb-theme-card' + (theme.name === current ? ' active' : '');
            card.dataset.theme = theme.name;

            var previewSrc = 'view/javascript/ockit/content-blocks/assets/img/themes/' + blockType + '/' + theme.name + '.png';
            card.innerHTML =
                '<span class="cb-type-card-icon">' +
                  '<img src="' + previewSrc + '" class="cb-theme-preview-img" alt="" onerror="this.style.display=\'none\'">' +
                '</span>' +
                '<span class="cb-type-card-name">' + theme.name + '</span>';
            grid.appendChild(card);
        });

        openModal('cb-modal-themes');
    }

    function addBlock(blockType, done) {
        var list = qs('#cb-blocks-list');
        var sortOrder = qsa('.cb-block', list).length;
        var loader = qs('#cb-add-loader');

        show(loader);
        post(editorUrls.block, { block_type: blockType, sort_order: sortOrder }, function (json) {
            hide(loader);
            if (json.error) { window.okNotify(json.error, 'error'); return; }
            var tmp = document.createElement('div');
            tmp.innerHTML = json.html;
            var blockEl = tmp.firstElementChild;
            // Newly added blocks open by default so the user can configure immediately
            blockEl.classList.remove('cb-block--collapsed');
            var chev = qs('.cb-btn-collapse i', blockEl);
            if (chev) chev.setAttribute('data-lucide', 'chevron-up');
            list.appendChild(blockEl);
            initWysiwygInEl(blockEl);
            hide(qs('#cb-empty'));
            initSortableInEl(blockEl);
            initLucide(blockEl);
            if (done) done(blockEl);
        }, function () { hide(loader); window.okNotify('Error loading block', 'error'); });
    }

    function deleteBlock(blockEl) {
        blockEl.remove();
        var list = qs('#cb-blocks-list');
        if (qsa('.cb-block', list).length === 0) show(qs('#cb-empty'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Add row / col / element
    // ─────────────────────────────────────────────────────────────────────────

    function addRow(blockEl) {
        var wrap = qs('.cb-rows-wrap', blockEl);
        if (!wrap) return;
        var rowHtml = '<div class="cb-row" data-row-id="0" data-sort="' + qsa('.cb-row', wrap).length + '">' +
            '<div class="cb-row-header">' +
            '<span class="ok-handle cb-handle"><i data-lucide="menu"></i></span>' +
            '<span class="cb-row-label ok-text-muted ok-text-sm"><i data-lucide="layout-list"></i> ' + (t.text_row || 'Row') + '</span>' +
            '<div class="cb-row-actions ok-ml-auto">' +
            '<button type="button" class="ok-btn ok-btn-default ok-btn-xs cb-btn-row-settings" title="' + (t.button_settings||'') + '"><i data-lucide="sliders-horizontal"></i></button>' +
            '<button type="button" class="ok-btn ok-btn-danger ok-btn-xs cb-btn-delete-row" title="' + (t.button_delete||'') + '"><i data-lucide="trash-2"></i></button>' +
            '</div></div>' +
            '<div class="cb-cols-wrap" data-sortable="cols"></div>' +
            '<div class="cb-row-footer"><button type="button" class="ok-btn ok-btn-default ok-btn-sm cb-btn-add-col ok-w-full">' +
            '<i data-lucide="plus"></i> ' + (t.button_add_col||'Колонка') + '</button></div>' +
            '</div>';
        insertHTML(wrap, rowHtml);
        var newRow = wrap.lastElementChild;
        if (newRow) initLucide(newRow);
        if (newRow && window.Sortable) {
            var colsWrap = qs('.cb-cols-wrap', newRow);
            if (colsWrap) new window.Sortable(colsWrap, { handle: '.cb-handle', animation: 150 });
        }
    }

    function addCol(rowEl) {
        var wrap = qs('.cb-cols-wrap', rowEl);
        if (!wrap) return;
        var colHtml = buildColHtml(0, qsa('.cb-col', wrap).length);
        insertHTML(wrap, colHtml);
        var newCol = wrap.lastElementChild;
        if (newCol) initLucide(newCol);
        if (newCol && window.Sortable) {
            var elWrap = qs('.cb-elements-wrap', newCol);
            if (elWrap) new window.Sortable(elWrap, { handle: '.cb-handle', animation: 150 });
        }
    }

    function addAccordionCol(blockEl) {
        var wrap = qs('.cb-cols-wrap', blockEl);
        if (!wrap) return;
        var idx = qsa('.cb-col', wrap).length;
        var colHtml = buildAccordionColHtml(idx);
        insertHTML(wrap, colHtml);
        var newCol = wrap.lastElementChild;
        if (newCol) initLucide(newCol);
        if (newCol && window.Sortable) {
            var elWrap = qs('.cb-elements-wrap', newCol);
            if (elWrap) new window.Sortable(elWrap, { handle: '.cb-handle', animation: 150 });
        }
    }

    function buildColHtml(colId, sortOrder) {
        var langTabsHtml = '';
        if (cbLangs.length > 1) {
            langTabsHtml = '<div class="cb-lang-tabs">' + cbLangs.map(function (l) {
                return '<button type="button" class="cb-lang-tab' + (l.language_id == cbLangId ? ' active' : '') + '" data-lang-id="' + l.language_id + '">' +
                    l.code.toUpperCase().slice(0, 2) + '</button>';
            }).join('') + '</div>';
        }
        var wBtns = '<button type="button" class="cb-col-width-btn active" data-w="0" title="' + (t.col_width_auto||'Авто') + '">A</button>' +
            [1,2,3,4,5,6,7,8,9,10,11,12].map(function (w) { return '<button type="button" class="cb-col-width-btn" data-w="' + w + '">' + w + '</button>'; }).join('');
        return '<div class="cb-col" data-col-id="' + colId + '" data-width="0" data-sort="' + sortOrder + '">' +
            '<div class="cb-col-header">' +
            '<span class="ok-handle cb-handle"><i data-lucide="menu"></i></span>' +
            '<div class="cb-col-width-group" data-current="0">' + wBtns + '</div>' +
            '<div class="cb-col-actions ok-ml-auto">' +
            '<div class="cb-add-el-wrap"><button type="button" class="ok-btn ok-btn-primary ok-btn-xs cb-btn-add-element-col">' +
            (t.button_add_element||'+ Елемент') + ' </button>' +
            '<div class="cb-el-dropdown" hidden>' +
            '<button type="button" class="cb-el-dropdown-item" data-el-type="text">' + (t.el_text||'Text') + '</button>' +
            '<button type="button" class="cb-el-dropdown-item" data-el-type="image">' + (t.el_image||'Image') + '</button>' +
            '<button type="button" class="cb-el-dropdown-item" data-el-type="html">' + (t.el_html||'HTML') + '</button>' +
            '<button type="button" class="cb-el-dropdown-item" data-el-type="video">' + (t.el_video||'Video') + '</button>' +
            '<button type="button" class="cb-el-dropdown-item" data-el-type="divider">' + (t.el_divider||'Divider') + '</button>' +
            '<button type="button" class="cb-el-dropdown-item" data-el-type="form">' + (t.el_form||'Form') + '</button>' +
            '</div></div>' +
            '<button type="button" class="ok-btn ok-btn-default ok-btn-xs cb-btn-col-settings"><i data-lucide="sliders-horizontal"></i></button>' +
            '<button type="button" class="ok-btn ok-btn-danger ok-btn-xs cb-btn-delete-col"><i data-lucide="trash-2"></i></button>' +
            '</div></div>' +
            '<div class="cb-elements-wrap" data-sortable="elements"></div>' +
            '</div>';
    }

    function buildAccordionColHtml(sortOrder) {
        var titleInputs = cbLangs.map(function (l) {
            return '<input type="text" class="ok-input ok-input-sm cb-acc-title' + (l.language_id != cbLangId ? ' hidden' : '') + '" ' +
                'data-lang="' + l.language_id + '" value="" placeholder="' + (t.placeholder_accordion_title||'') + '">';
        }).join('');
        var langTabs = cbLangs.length > 1 ? '<div class="cb-lang-tabs cb-lang-tabs-inline">' + cbLangs.map(function (l) {
            return '<button type="button" class="cb-lang-tab' + (l.language_id == cbLangId ? ' active' : '') + '" data-lang-id="' + l.language_id + '">' +
                l.code.toUpperCase().slice(0, 2) + '</button>';
        }).join('') + '</div>' : '';
        return '<div class="cb-col cb-accordion-panel" data-col-id="0" data-sort="' + sortOrder + '">' +
            '<div class="cb-col-header">' +
            '<span class="ok-handle cb-handle"><i data-lucide="menu"></i></span>' +
            langTabs + titleInputs +
            '<div class="cb-col-actions ok-ml-auto">' +
            '<button type="button" class="ok-btn ok-btn-default ok-btn-xs cb-btn-col-settings"><i data-lucide="sliders-horizontal"></i></button>' +
            '<button type="button" class="ok-btn ok-btn-danger ok-btn-xs cb-btn-delete-col"><i data-lucide="trash-2"></i></button>' +
            '</div></div>' +
            '<div class="cb-elements-wrap" data-sortable="elements"></div>' +
            '<div class="cb-col-footer"><div class="cb-add-el-wrap">' +
            '<button type="button" class="ok-btn ok-btn-primary ok-btn-xs cb-btn-add-element-col">' +
            (t.button_add_element||'+ Елемент') + ' </button>' +
            '<div class="cb-el-dropdown" hidden>' +
            '<button type="button" class="cb-el-dropdown-item" data-el-type="text">' + (t.el_text||'Text') + '</button>' +
            '<button type="button" class="cb-el-dropdown-item" data-el-type="image">' + (t.el_image||'Image') + '</button>' +
            '<button type="button" class="cb-el-dropdown-item" data-el-type="html">' + (t.el_html||'HTML') + '</button>' +
            '<button type="button" class="cb-el-dropdown-item" data-el-type="video">' + (t.el_video||'Video') + '</button>' +
            '<button type="button" class="cb-el-dropdown-item" data-el-type="form">' + (t.el_form||'Form') + '</button>' +
            '</div></div></div></div>';
    }

    function addElementToCol(colEl, elType) {
        fetchElement(elType, '', function (html) {
            var wrap = qs('.cb-elements-wrap', colEl);
            if (!wrap) return;
            insertHTML(wrap, html);
            var newEl = wrap.lastElementChild;
            if (newEl) {
                initWysiwygInEl(newEl);
                initImagePicker(newEl);
                initLucide(newEl);
            }
        });
    }

    function addFlatElement(blockEl, elType) {
        fetchElement(elType, blockEl.dataset.blockType || '', function (html) {
            var wrap = qs('.cb-elements-wrap', blockEl);
            if (!wrap) return;
            insertHTML(wrap, html);
            var newEl = wrap.lastElementChild;
            if (newEl) { initImagePicker(newEl); initLucide(newEl); }
        });
    }

    function fetchElement(elType, blockType, done) {
        post(editorUrls.element, { el_type: elType, block_type: blockType, sort_order: 0 }, function (json) {
            if (json.error) { window.okNotify(json.error, 'error'); return; }
            done(json.html);
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Duplicate block
    // ─────────────────────────────────────────────────────────────────────────

    function duplicateBlock(blockEl) {
        var blockId = parseInt(blockEl.dataset.blockId || 0, 10);
        if (!blockId) { window.okNotify(t.text_demo_warn_save_first || 'Save blocks before duplicating', 'warning'); return; }

        var loader = qs('#cb-add-loader');
        show(loader);
        post(editorUrls.duplicate, { block_id: blockId }, function (json) {
            hide(loader);
            if (json.error) { window.okNotify(json.error, 'error'); return; }
            if (json.html) {
                var list = qs('#cb-blocks-list');
                var tmp = document.createElement('div');
                tmp.innerHTML = json.html;
                var newBlockEl = tmp.firstElementChild;
                blockEl.insertAdjacentElement('afterend', newBlockEl);
                initLucide(newBlockEl);
                initImagePicker(newBlockEl);
                initWysiwygInEl(newBlockEl);
                initSortableInEl(newBlockEl);
                hide(qs('#cb-empty'));
            }
            window.okNotify(json.success || t.text_block_duplicated || 'Продубльовано', 'success');
        }, function () { hide(loader); window.okNotify('Error', 'error'); });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Video thumbnail fetch
    // ─────────────────────────────────────────────────────────────────────────

    function fetchVideoThumb(elEl) {
        var link  = (qs('.cb-video-link', elEl) || {}).value || '';
        var thumb = qs('.cb-video-thumb', elEl);
        var vidId = qs('.cb-video-id', elEl);
        if (!link) return;

        post(editorUrls.video, { video_link: link }, function (json) {
            if (json.error) { window.okNotify(json.error, 'error'); return; }
            if (thumb) { thumb.src = json.thumb; show(thumb); }
            if (vidId) vidId.value = json.video_id;
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Autocomplete
    // ─────────────────────────────────────────────────────────────────────────

    function fetchAutocomplete(type, query, wrapEl) {
        var dd = qs('.cb-autocomplete-dropdown', wrapEl);
        fetchAutocompleteInto(type, query, dd, function (item) {
            selectAutocomplete(wrapEl, item);
            hide(dd);
        });
    }

    // Render dropdown options into a given container; onPick invoked with the item
    function fetchAutocompleteInto(type, query, dd, onPick) {
        if (!dd) return;
        get(editorUrls.autocomplete, { type: type, filter_name: query, limit: 20 }, function (results) {
            dd.innerHTML = '';
            if (!results.length) { hide(dd); return; }
            results.forEach(function (item) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'cb-ac-option';
                btn.dataset.id = item.id;
                btn.dataset.name = item.name;
                btn.dataset.img = item.image || '';
                if (item.image) {
                    var img = document.createElement('img');
                    img.src = item.image;
                    img.width = 28;
                    img.height = 28;
                    btn.appendChild(img);
                }
                var span = document.createElement('span');
                span.textContent = item.name;
                btn.appendChild(span);
                btn.addEventListener('click', function () { onPick(item); });
                dd.appendChild(btn);
            });
            show(dd);
        });
    }

    function addMultitagTag(multitag, item) {
        var tags = qs('.ok-multitag-tags', multitag);
        if (!tags) return;
        // Skip if already added
        if (qs('.ok-multitag-tag[data-id="' + item.id + '"]', tags)) return;
        var tag = document.createElement('span');
        tag.className = 'ok-multitag-tag';
        tag.dataset.id = item.id;
        if (item.image) {
            var img = document.createElement('img');
            img.src = item.image;
            img.width = 20;
            img.height = 20;
            tag.appendChild(img);
        }
        tag.appendChild(document.createTextNode(' ' + (item.name || ('#' + item.id))));
        var rm = document.createElement('button');
        rm.type = 'button';
        rm.className = 'ok-multitag-remove';
        rm.setAttribute('aria-label', 'remove');
        rm.textContent = '×';
        tag.appendChild(rm);
        tags.appendChild(tag);
    }

    function selectAutocomplete(wrapEl, item) {
        var elEl = wrapEl.closest('.cb-element');
        if (!elEl) return;

        // Keep the input visible so the user can search a new product without
        // a separate clear button — picking a new one overwrites the selection.
        var inp = qs('.cb-autocomplete-input', wrapEl);
        if (inp) { inp.value = ''; }

        var selected = qs('.cb-autocomplete-selected', wrapEl);
        var nameEl   = qs('.cb-autocomplete-name', wrapEl);
        var thumbEl  = qs('.cb-autocomplete-thumb', wrapEl);
        if (selected) show(selected);
        if (nameEl)   nameEl.textContent = item.name;
        if (thumbEl)  thumbEl.src = item.image || thumbEl.dataset.placeholder || '';

        // Per-element wrap with data-target (e.g. reviews_item source bindings)
        // writes to scoped hidden inputs inside the wrap; otherwise legacy
        // single-target (.cb-ac-val/name/img on the element root).
        var target = wrapEl.dataset && wrapEl.dataset.target;
        if (target) {
            var hIdLocal   = qs('[class*="cb-src-"][class$="-id"]', wrapEl);
            var hNameLocal = qs('[class*="cb-src-"][class$="-name"]', wrapEl);
            if (hIdLocal)   hIdLocal.value   = item.id;
            if (hNameLocal) hNameLocal.value = item.name;
        } else {
            var valHidden  = qs('.cb-ac-val',  elEl);
            var nameHidden = qs('.cb-ac-name', elEl);
            var imgHidden  = qs('.cb-ac-img',  elEl);
            if (valHidden)  valHidden.value  = item.id;
            if (nameHidden) nameHidden.value = item.name;
            if (imgHidden)  imgHidden.value  = item.image || '';
        }

        // Update visible header label + thumbnail (carousel_product, categories_item, etc.)
        var headerLabel = qs('.cb-item-label', elEl);
        if (headerLabel) headerLabel.textContent = item.name;
        var headerThumb = qs('.cb-item-thumb', elEl);
        if (item.image) {
            if (headerThumb) {
                headerThumb.src = item.image;
            } else {
                var header = qs('.cb-element-header', elEl);
                var anchor = qs('.cb-handle', elEl);
                if (header && anchor) {
                    var img = document.createElement('img');
                    img.className = 'cb-item-thumb';
                    img.alt = '';
                    img.src = item.image;
                    anchor.insertAdjacentElement('afterend', img);
                }
            }
        }

        // Hide picker panel after select
        var panel = elEl.querySelector('.cb-autocomplete-panel');
        if (panel) hide(panel);

        hide(qs('.cb-autocomplete-dropdown', wrapEl));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ─────────────────────────────────────────────────────────────────────────
    // Image picker — OC3 standard data-toggle="image"
    // Assigns unique IDs to anchor + hidden input so OC3's common.js handler
    // can open the filemanager AJAX modal correctly.
    // ─────────────────────────────────────────────────────────────────────────

    function initLucide(root) {
        if (window.lucide) {
            var nodes = root ? [root] : undefined;
            window.lucide.createIcons(nodes ? { nodes: nodes } : undefined);
        }
    }

    function initImagePicker(root) {
        qsa('.cb-image-picker-wrap[data-cb-img-init]', root || document).forEach(function (wrap) {
            var n      = ++cbImgIdCounter;
            var anchor = qs('[data-toggle="image"]', wrap);
            // Either standard image element val OR product override-image val
            var input  = qs('.cb-image-val, .cb-override-image-val', wrap);
            if (anchor) anchor.id = 'cbp-thumb-' + n;
            if (input)  input.id  = 'cbp-input-' + n;
            wrap.removeAttribute('data-cb-img-init');

            // Watch img src attribute change (OC3 filemanager sets it via jQuery)
            var img = anchor ? qs('img', anchor) : null;
            var row = wrap.parentElement; // cb-image-row, cb-video-poster-row, etc.
            if (img && row) {
                new MutationObserver(function () {
                    var ph     = img.dataset.placeholder || '';
                    var src    = img.getAttribute('src') || '';
                    var hasImg = src && src !== ph;
                    // Toggle nearest clear button (video-poster / video-local tiles).
                    // Plain images and product override-image rely on the OC file-picker
                    // popover's own clear control — no extra buttons.
                    var clr = qs('.cb-btn-clear-video-poster, .cb-btn-clear-video-local, .cb-btn-clear-video-thumb', row);
                    if (clr) hasImg ? show(clr) : hide(clr);
                }).observe(img, { attributes: true, attributeFilter: ['src'] });
            }
        });

        // After OC3 filemanager modal closes, sync video local-tile clear button
        if (window.$ && !initImagePicker._modalBound) {
            initImagePicker._modalBound = true;
            $(document).on('hidden.bs.modal', '#modal-image', function () {
                qsa('.cb-video-local-val').forEach(function (inp) {
                    var tile = inp.closest('.cb-video-tile');
                    if (!tile) return;
                    var clrBtn = qs('.cb-btn-clear-video-local', tile);
                    if (clrBtn) inp.value ? show(clrBtn) : hide(clrBtn);
                });
            });
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Image upload (drag&drop / file input)
    // ─────────────────────────────────────────────────────────────────────────

    function uploadImage(elEl, file, pickRow) {
        if (!editorUrls.upload) return;

        // Scope the lookup to the specific .cb-image-row (so product override,
        // video poster/local tiles each find *their* thumb/value, not the first
        // hit anywhere on the element).
        var pick = pickRow || qs('.cb-image-row', elEl);
        if (pick) pick.classList.add('cb-uploading');

        var fd  = new FormData();
        fd.append('file', file);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', editorUrls.upload, true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4) return;
            if (pick) pick.classList.remove('cb-uploading');
            try {
                var json = JSON.parse(xhr.responseText);
                if (json.error) { window.okNotify(json.error, 'error'); return; }
                var scope = pick || elEl;
                var img = qs('.cb-image-thumb', scope);
                var val = qs('.cb-image-val, .cb-override-image-val, .cb-video-poster-val, .cb-video-local-val', scope);
                if (img) img.src = json.url;
                if (val) val.value = json.path;
            } catch (err) {
                window.okNotify('Upload error', 'error');
            }
        };
        xhr.send(fd);
    }

    // Multi-file bulk upload → creates one carousel_image element per file.
    function handleMultiUpload(zone, files) {
        if (!files || !files.length) return;
        var blockEl = zone.closest('.cb-block');
        if (!blockEl) return;
        var elType  = zone.dataset.multiAdd || 'carousel_image';
        var wrap    = qs('.cb-elements-wrap', blockEl);
        if (!wrap) return;

        zone.classList.add('cb-uploading');
        var pending = files.length;
        var done = function () { pending--; if (pending <= 0) zone.classList.remove('cb-uploading'); };

        Array.prototype.forEach.call(files, function (file) {
            var fd = new FormData(); fd.append('file', file);
            var xhr = new XMLHttpRequest();
            xhr.open('POST', editorUrls.upload, true);
            xhr.onreadystatechange = function () {
                if (xhr.readyState !== 4) { return; }
                try {
                    var json = JSON.parse(xhr.responseText);
                    if (json.error) { window.okNotify(json.error, 'error'); done(); return; }
                    fetchElement(elType, blockEl.dataset.blockType || '', function (html) {
                        wrap.insertAdjacentHTML('beforeend', html);
                        var newEl = wrap.lastElementChild;
                        if (newEl) {
                            initLucide(newEl);
                            initImagePicker(newEl);
                            // Set the new element's image hidden val + visible thumb
                            var hid = qs('.cb-image-val', newEl);
                            var img = qs('.cb-image-thumb', newEl);
                            if (hid) hid.value = json.path;
                            if (img) img.src   = json.url;
                            var clr = qs('.cb-btn-clear-image', newEl);
                            if (clr) show(clr);
                        }
                        done();
                    });
                } catch (err) { window.okNotify('Upload error', 'error'); done(); }
            };
            xhr.send(fd);
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Settings modal
    // ─────────────────────────────────────────────────────────────────────────

    function openSettings(targetEl, type) {
        settingsTarget = { el: targetEl, type: type };

        var modal = qs('#cb-modal-settings');
        if (!modal) return;

        var titleEl = qs('#cb-modal-settings-title', modal);
        var titleMap = { block: t.modal_title_block, row: t.modal_title_row, col: t.modal_title_col, element: t.modal_title_element };
        if (titleEl) titleEl.textContent = titleMap[type] || type;

        // Hide Display panel for block-level settings (the block header already
        // exposes a device-visibility control via `.cb-device-btn`).
        var dispPanel = qs('#ms-display-panel', modal);
        if (dispPanel) { type === 'block' ? hide(dispPanel) : show(dispPanel); }

        // Load current values from data-params attribute
        var params = {};
        try { params = JSON.parse(targetEl.dataset.params || '{}'); } catch (e) {}

        var setVal = function (id, val) {
            var el = qs('#' + id, modal);
            if (el) el.value = val || '';
        };
        var setChk = function (id, val) {
            var el = qs('#' + id, modal);
            if (el) el.checked = !!val;
        };

        setVal('ms-bg-color',      params.bg_color      || '');
        setVal('ms-text-color',    params.text_color    || '');
        setVal('ms-font-size',     params.font_size     || '');
        setVal('ms-font-weight',   params.font_weight   || '');
        setVal('ms-text-align',    params.text_align    || '');
        setVal('ms-border-radius', params.border_radius || '');
        setVal('ms-border',        params.border        || '');

        // Parse border shorthand "1px solid #ccc" → 3 selects
        (function () {
            var b = String(params.border || '').trim();
            var w = '', s = '', c = '';
            if (b) {
                var parts = b.split(/\s+/);
                for (var i = 0; i < parts.length; i++) {
                    var p = parts[i];
                    if (/^\d+(px|em|rem|%)?$/.test(p)) w = p;
                    else if (/^(solid|dashed|dotted|double|none|hidden|groove|ridge|inset|outset)$/.test(p)) s = p;
                    else c = p;
                }
            }
            setVal('ms-border-width', w);
            setVal('ms-border-style', s);
            setVal('ms-border-color', c);
        }());
        setVal('ms-custom-class',  params.custom_class  || '');
        setVal('ms-preset',        params.preset        || '');

        var pad = params.padding || {};
        setVal('ms-pt', pad.top    || '');
        setVal('ms-pr', pad.right  || '');
        setVal('ms-pb', pad.bottom || '');
        setVal('ms-pl', pad.left   || '');

        var mrg = params.margin || {};
        setVal('ms-mt', mrg.top    || '');
        setVal('ms-mr', mrg.right  || '');
        setVal('ms-mb', mrg.bottom || '');
        setVal('ms-ml', mrg.left   || '');

        var hide2 = params.hide_on || {};
        setChk('ms-hide-mobile',  hide2.mobile);
        setChk('ms-hide-tablet',  hide2.tablet);
        setChk('ms-hide-desktop', hide2.desktop);

        // Sync range inputs to populated number values
        ['ms-font-size', 'ms-border-radius'].forEach(function (numId) {
            var numEl   = qs('#' + numId, modal);
            var rangeEl = qs('#' + numId + '-range', modal);
            if (numEl && rangeEl) rangeEl.value = numEl.value || 0;
        });

        // Modal is now flat (no tabs) — scroll body to top
        var modalBody = qs('.cb-modal-body', modal);
        if (modalBody) modalBody.scrollTop = 0;

        openModal('cb-modal-settings');

        // Re-bind Coloris to .coloris inputs inside the modal (binding is lost
        // when the modal is hidden/shown multiple times)
        if (window.Coloris) {
            try { Coloris({ el: '#cb-modal-settings .coloris', theme: 'default', format: 'hex', formatToggle: false }); } catch (e) {}
        }
    }

    function applySettings() {
        if (!settingsTarget) return;
        var targetEl = settingsTarget.el;
        var modal = qs('#cb-modal-settings');

        var getVal = function (id) { return (qs('#' + id, modal) || {}).value || ''; };
        var getChk = function (id) { return !!(qs('#' + id, modal) || {}).checked; };

        // Compose border shorthand from 3 selects
        var bw = getVal('ms-border-width');
        var bs = getVal('ms-border-style');
        var bc = getVal('ms-border-color');
        var border = (bw && bs) ? (bw + ' ' + bs + (bc ? ' ' + bc : '')) : '';

        var params = {
            bg_color:      getVal('ms-bg-color'),
            text_color:    getVal('ms-text-color'),
            font_size:     getVal('ms-font-size'),
            font_weight:   getVal('ms-font-weight'),
            text_align:    getVal('ms-text-align'),
            border_radius: getVal('ms-border-radius'),
            border:        border,
            custom_class:  getVal('ms-custom-class'),
            preset:        getVal('ms-preset'),
            padding: {
                top:    getVal('ms-pt'),
                right:  getVal('ms-pr'),
                bottom: getVal('ms-pb'),
                left:   getVal('ms-pl')
            },
            margin: {
                top:    getVal('ms-mt'),
                right:  getVal('ms-mr'),
                bottom: getVal('ms-mb'),
                left:   getVal('ms-ml')
            },
            hide_on: {
                mobile:  getChk('ms-hide-mobile'),
                tablet:  getChk('ms-hide-tablet'),
                desktop: getChk('ms-hide-desktop')
            }
        };

        targetEl.dataset.params = JSON.stringify(params);
        closeModal('cb-modal-settings');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Templates
    // ─────────────────────────────────────────────────────────────────────────

    function openTemplatesModal() {
        loadTemplates('');
        openModal('cb-modal-templates');
    }

    function loadTemplates(filterType) {
        post(editorUrls.templates, { action: 'list', block_type: filterType }, function (json) {
            var listEl = qs('#cb-templates-list');
            var noEl   = qs('#cb-no-templates');
            if (!listEl) return;
            listEl.innerHTML = '';
            if (!json.templates || !json.templates.length) {
                show(noEl);
                return;
            }
            hide(noEl);
            json.templates.forEach(function (tpl) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'cb-tpl-card';
                btn.dataset.tplId = tpl.template_id;
                btn.innerHTML = '<span class="cb-tpl-name">' + tpl.name + '</span>' +
                    '<span class="ok-badge ok-badge-muted ok-badge-sm">' + tpl.block_type + '</span>' +
                    '<button type="button" class="ok-btn ok-btn-danger ok-btn-xs cb-btn-delete-tpl" data-tpl-id="' + tpl.template_id + '"><i data-lucide="trash-2"></i></button>';
                btn.addEventListener('click', function (ev) {
                    if (ev.target.closest('.cb-btn-delete-tpl')) {
                        deleteTemplate(tpl.template_id, filterType, btn);
                        return;
                    }
                    loadTemplate(tpl.template_id);
                });
                listEl.appendChild(btn);
            });
            initLucide(listEl);
        });
    }

    function deleteTemplate(id, filterType, rowEl) {
        post(editorUrls.templates, { action: 'delete', template_id: id }, function (json) {
            if (json.success) { rowEl.remove(); } else { window.okNotify(json.error, 'error'); }
        });
    }

    function loadTemplate(id) {
        post(editorUrls.templates, { action: 'load', template_id: id }, function (json) {
            if (json.error) { window.okNotify(json.error, 'error'); return; }
            closeModal('cb-modal-templates');
            var tpl = json.template;
            addBlock(tpl.block_type, function (blockEl) {
                populateBlockFromTemplate(blockEl, tpl);
            });
        });
    }

    function populateBlockFromTemplate(blockEl, tpl) {
        var d = tpl.data || {};

        // Set block name from template name
        var nameEl = qs('.cb-block-name', blockEl);
        if (nameEl && tpl.name) nameEl.value = tpl.name;

        // Set theme
        var themeEl = qs('.cb-block-theme', blockEl);
        if (themeEl && d.theme && d.theme !== 'default') themeEl.value = d.theme;

        var structure = blockEl.dataset.structure || 'elements';

        if (structure === 'rows' && d.rows) {
            d.rows.forEach(function (rowData) {
                addRow(blockEl);
                var rowsWrap = qs('.cb-rows-wrap', blockEl);
                var rowEl = rowsWrap ? rowsWrap.lastElementChild : null;
                if (!rowEl || !rowData.cols) return;
                rowData.cols.forEach(function (colData) {
                    addCol(rowEl);
                    var colsWrap = qs('.cb-cols-wrap', rowEl);
                    var colEl = colsWrap ? colsWrap.lastElementChild : null;
                    if (!colEl) return;
                    // Set col width
                    if (colData.width) {
                        colEl.dataset.width = colData.width;
                        var wg = qs('.cb-col-width-group', colEl);
                        if (wg) {
                            wg.dataset.current = colData.width;
                            var cw = parseInt(colData.width, 10);
                            qsa('.cb-col-width-btn', wg).forEach(function (b) {
                                var bw = parseInt(b.dataset.w, 10);
                                b.classList.toggle('active', bw === cw);
                                b.classList.toggle('lit', bw > 0 && bw < cw);
                            });
                        }
                    }
                    (colData.elements || []).forEach(function (elData) {
                        fetchElementWithData(elData.el_type, tpl.block_type, elData, function (elEl) {
                            var wrap = qs('.cb-elements-wrap', colEl);
                            if (!wrap) return;
                            wrap.appendChild(elEl);
                            initWysiwygInEl(elEl);
                        });
                    });
                });
            });
        } else if (d.elements) {
            d.elements.forEach(function (elData) {
                fetchElementWithData(elData.el_type, tpl.block_type, elData, function (elEl) {
                    var wrap = qs('.cb-elements-wrap', blockEl);
                    if (!wrap) return;
                    wrap.appendChild(elEl);
                    initWysiwygInEl(elEl);
                });
            });
        }
    }

    function fetchElementWithData(elType, blockType, elData, done) {
        post(editorUrls.element, { el_type: elType, block_type: blockType, sort_order: 0 }, function (json) {
            if (json.error) return;
            var tmp = document.createElement('div');
            tmp.innerHTML = json.html;
            var elEl = tmp.firstElementChild;
            if (!elEl) return;
            // Pre-fill content on .cb-wysiwyg divs BEFORE appending to DOM (before Jodit init)
            fillElementData(elEl, elData);
            done(elEl);
        });
    }

    function fillElementData(elEl, elData) {
        if (!elData) return;
        if (elData.params) {
            var tagSel = qs('.cb-el-tag', elEl);
            if (tagSel && elData.params.tag) tagSel.value = elData.params.tag;
        }
        var data = elData.data || {};
        Object.keys(data).forEach(function (lid) {
            var ld = data[lid] || {};
            var wysiwyg = qs('.cb-wysiwyg[data-lang="' + lid + '"]', elEl);
            if (wysiwyg && ld.content) wysiwyg.innerHTML = ld.content;
            var htmlArea = qs('.cb-html-area[data-lang="' + lid + '"]', elEl);
            if (htmlArea && ld.content) htmlArea.value = ld.content;
            var faqQ = qs('.cb-faq-question[data-lang="' + lid + '"]', elEl);
            if (faqQ && ld.question) faqQ.value = ld.question;
            var faqA = qs('.cb-faq-answer[data-lang="' + lid + '"]', elEl);
            if (faqA && ld.answer) faqA.value = ld.answer;
            var altInp = qs('.cb-image-alt[data-lang="' + lid + '"]', elEl);
            if (altInp && ld.alt) altInp.value = ld.alt;
            var urlInp = qs('.cb-image-url[data-lang="' + lid + '"]', elEl);
            if (urlInp && ld.url) urlInp.value = ld.url;
            var imageVal = qs('.cb-image-val', elEl);
            if (imageVal && ld.image) imageVal.value = ld.image;
        });
    }

    document.addEventListener('change', function (e) {
        if (e.target.id === 'cb-tpl-filter-type') {
            loadTemplates(e.target.value);
        }
    });

    function saveAsTemplate() {
        if (!templateSavingBlockEl) return;
        var name = (qs('#cb-tpl-name-input') || {}).value || '';
        if (!name) { window.okNotify(t.entry_template_name + ' required', 'warning'); return; }

        var blockData = collectBlockData(templateSavingBlockEl);
        post(editorUrls.templates, {
            action:     'save',
            name:       name,
            block_type: templateSavingBlockEl.dataset.blockType,
            data:       JSON.stringify(blockData)
        }, function (json) {
            if (json.error) { window.okNotify(json.error, 'error'); return; }
            window.okNotify(t.text_template_saved || 'Шаблон збережено', 'success');
            closeModal('cb-modal-save-template');
            qs('#cb-tpl-name-input').value = '';
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Translate
    // ─────────────────────────────────────────────────────────────────────────

    function doTranslate() {
        if (!translateBlockEl) return;
        var sel = qs('#cb-translate-lang');
        if (!sel) return;
        var opt = sel.options[sel.selectedIndex];
        var lang  = opt.value;
        var langId = opt.dataset.langId;

        var srcSel = qs('#cb-translate-lang-from');
        var srcLang = '', srcLangId = '';
        if (srcSel) {
            var srcOpt = srcSel.options[srcSel.selectedIndex];
            srcLang   = srcOpt.value;
            srcLangId = srcOpt.dataset.langId;
        }

        var blockData = collectBlockData(translateBlockEl);
        var statusEl  = qs('#cb-translate-status');
        if (statusEl) { statusEl.textContent = t.text_translating || 'Перекладаємо...'; show(statusEl); }

        var btn = qs('#cb-btn-translate-confirm');
        if (btn) btn.disabled = true;

        post(editorUrls.translate, {
            block_data:     JSON.stringify(blockData),
            source_lang:    srcLang,
            source_lang_id: srcLangId,
            target_lang:    lang,
            target_lang_id: langId
        }, function (json) {
            if (btn) btn.disabled = false;
            if (json.error) {
                window.okNotify(json.error, 'error');
                if (statusEl) hide(statusEl);
                return;
            }
            window.okNotify(json.success || (t.text_translated + lang), 'success');
            closeModal('cb-modal-translate');
            if (statusEl) hide(statusEl);
            // Reload translated data into block DOM
            applyTranslatedData(translateBlockEl, json.block_data, parseInt(langId, 10));
        });
    }

    function applyTranslatedData(blockEl, blockData, langId) {
        // Apply translated content back to DOM elements
        var elements = (blockData.rows || []).reduce(function (acc, row) {
            (row.cols || []).forEach(function (col) {
                (col.elements || []).forEach(function (el) { acc.push(el); });
            });
            return acc;
        }, []).concat(blockData.elements || []);

        elements.forEach(function (elData) {
            var elId = elData.element_id;
            if (!elId) return;
            var elEl = qs('.cb-element[data-el-id="' + elId + '"]', blockEl);
            if (!elEl) return;
            var langData = (elData.data || {})[langId] || {};
            // content
            var wysiwyg = qs('.cb-wysiwyg[data-lang="' + langId + '"]', elEl);
            if (wysiwyg && langData.content) {
                if (wysiwyg.tagName === 'TEXTAREA' && wysiwyg.jodit) {
                    wysiwyg.jodit.setEditorValue(langData.content);
                } else if (wysiwyg.tagName === 'TEXTAREA') {
                    wysiwyg.value = langData.content;
                } else {
                    wysiwyg.innerHTML = langData.content;
                }
            }
            var htmlArea = qs('.cb-html-area[data-lang="' + langId + '"]', elEl);
            if (htmlArea && langData.content) htmlArea.value = langData.content;
            var faqQ = qs('.cb-faq-question[data-lang="' + langId + '"]', elEl);
            if (faqQ && langData.question) faqQ.value = langData.question;
            var faqA = qs('.cb-faq-answer[data-lang="' + langId + '"]', elEl);
            if (faqA && langData.answer) faqA.value = langData.answer;
            var altInp = qs('.cb-image-alt[data-lang="' + langId + '"]', elEl);
            if (altInp && langData.alt) altInp.value = langData.alt;
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Save blocks
    // ─────────────────────────────────────────────────────────────────────────

    function saveBlocks() {
        var edEl = qs('#cb-editor');
        if (!edEl) return;

        var blocks = qsa('.cb-block', qs('#cb-blocks-list')).map(collectBlockData);

        post(editorUrls.save, {
            page_route: edEl.dataset.pageRoute,
            page_id:    edEl.dataset.pageId,
            blocks:     JSON.stringify(blocks)
        }, function (json) {
            if (json.error) { window.okNotify(json.error, 'error'); return; }
            window.okNotify(json.success || t.text_blocks_saved || 'Збережено', 'success');
            // Update block IDs
            if (json.block_ids) {
                var bls = qsa('.cb-block', qs('#cb-blocks-list'));
                bls.forEach(function (bl, i) {
                    if (json.block_ids[i]) bl.dataset.blockId = json.block_ids[i];
                });
            }
        }, function (err) {
            window.okNotify(err || t.error_save_failed || 'Помилка', 'error');
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Data collection from DOM
    // ─────────────────────────────────────────────────────────────────────────

    function collectBlockData(blockEl) {
        var structure = blockEl.dataset.structure || 'elements';
        var nameEl    = qs('.cb-block-name', blockEl);
        var themeEl   = qs('.cb-block-theme', blockEl);
        var statusEl  = qs('.cb-block-status', blockEl);

        var params = safeParseParams(blockEl.dataset.params);
        var devDisplay = {};
        qsa('.cb-device-btn', blockEl).forEach(function (btn) {
            devDisplay[btn.dataset.device] = btn.classList.contains('active') ? 1 : 0;
        });
        params.device_display = devDisplay;

        // Responsive order (per-block)
        var respOrder = {};
        qsa('.cb-resp-order-input', blockEl).forEach(function (inp) {
            respOrder[inp.dataset.device] = inp.value;
        });
        params.responsive_order = respOrder;

        // Per-block param overrides
        var paramsPanel = qs('.cb-block-params', blockEl);
        if (paramsPanel) {
            // Toggle buttons
            qsa('.cb-param-btn', paramsPanel).forEach(function (btn) {
                var key = btn.dataset.paramKey;
                if (key) params[key] = btn.classList.contains('active') ? 1 : 0;
            });
            // Number / device / multitag inputs
            qsa('.cb-block-param-item', paramsPanel).forEach(function (item) {
                var key = item.dataset.paramKey;
                if (!key) return;
                var numberEl = qs('.cb-block-param-number', item);
                var deviceInputs = qsa('.cb-block-param-device-input', item);
                var multitag = qs('.cb-block-param-multitag', item);
                if (multitag) {
                    var ids = qsa('.ok-multitag-tag', multitag).map(function (t) {
                        return parseInt(t.dataset.id || 0, 10);
                    }).filter(Boolean);
                    params[key] = ids;
                } else if (numberEl) {
                    params[key] = numberEl.value;
                } else if (deviceInputs.length) {
                    var dvals = {};
                    deviceInputs.forEach(function (inp) { dvals[inp.dataset.device] = inp.value; });
                    params[key] = dvals;
                }
            });
        }

        var data = {
            block_id:   parseInt(blockEl.dataset.blockId || 0, 10),
            block_type: blockEl.dataset.blockType || '',
            block_name: nameEl ? nameEl.value : '',
            theme:      themeEl ? themeEl.value : 'default',
            status:     statusEl ? (statusEl.checked ? 1 : 0) : 1,
            sort_order: parseInt(blockEl.dataset.sort || 0, 10),
            params:     params,
            rows:       [],
            elements:   []
        };

        if (structure === 'rows') {
            data.rows = collectRows(blockEl);
        } else if (structure === 'cols') {
            // Accordion: single implicit row
            data.rows = [{ row_id: 0, sort_order: 0, params: {}, cols: collectAccordionCols(blockEl) }];
        } else {
            data.elements = collectFlatElements(blockEl);
        }

        return data;
    }

    function collectRows(blockEl) {
        return qsa('.cb-rows-wrap > .cb-row', blockEl).map(function (rowEl, ri) {
            return {
                row_id:    parseInt(rowEl.dataset.rowId || 0, 10),
                sort_order: ri,
                params:    safeParseParams(rowEl.dataset.params),
                cols:      collectCols(rowEl)
            };
        });
    }

    function collectCols(rowEl) {
        return qsa('.cb-cols-wrap > .cb-col', rowEl).map(function (colEl, ci) {
            return {
                col_id:    parseInt(colEl.dataset.colId || 0, 10),
                width:     parseInt(colEl.dataset.width || 0, 10),
                sort_order: ci,
                params:    safeParseParams(colEl.dataset.params),
                elements:  collectColElements(colEl)
            };
        });
    }

    function collectAccordionCols(blockEl) {
        return qsa('.cb-accordion-cols > .cb-col', blockEl).map(function (colEl, ci) {
            var titles = {};
            qsa('.cb-acc-title', colEl).forEach(function (inp) { titles[inp.dataset.lang] = inp.value; });
            var params = safeParseParams(colEl.dataset.params);
            params.title = titles;
            return {
                col_id:    parseInt(colEl.dataset.colId || 0, 10),
                width:     0,
                sort_order: ci,
                params:    params,
                elements:  collectColElements(colEl)
            };
        });
    }

    function collectColElements(colEl) {
        return qsa('.cb-elements-wrap > .cb-element', colEl).map(function (elEl, ei) {
            return extractElement(elEl, ei);
        });
    }

    function collectFlatElements(blockEl) {
        return qsa('.cb-elements-wrap > .cb-element', blockEl).map(function (elEl, ei) {
            return extractElement(elEl, ei);
        });
    }

    function extractElement(elEl, sortIndex) {
        var elType = elEl.dataset.elType || '';
        var data = {
            element_id:   parseInt(elEl.dataset.elId || 0, 10),
            element_type: elType,
            sort_order:   sortIndex,
            params:       safeParseParams(elEl.dataset.params),
            data:         {}
        };

        // Extract params from DOM inputs (tag, author, rating, etc.)
        var tagSel = qs('.cb-el-tag', elEl);
        if (tagSel) data.params.tag = tagSel.value;

        // Video element per-element params (button-group state)
        var pjsEnable = qs('.cb-video-pjs-enable', elEl);
        if (pjsEnable) data.params.playerjs_enable = pjsEnable.classList.contains('active') ? 1 : 0;
        var vidAutoplay = qs('.cb-video-autoplay', elEl);
        var vidVertical = qs('.cb-video-vertical', elEl);
        if (vidAutoplay) data.params.autoplay = vidAutoplay.classList.contains('active') ? 1 : 0;
        if (vidVertical) data.params.vertical = vidVertical.classList.contains('active') ? 1 : 0;

        var ratingEl = qs('.cb-review-rating', elEl);
        if (ratingEl) data.params.rating = parseInt(ratingEl.value, 10);
        var authorEl = qs('.cb-review-author', elEl);
        if (authorEl) data.params.author = authorEl.value;

        // Form element — params are the entire form-builder JSON
        if (elType === 'form') {
            var formJson = qs('.cb-form-params-json', elEl);
            if (formJson && formJson.value) {
                try { data.params = JSON.parse(formJson.value); } catch (e) {}
            }
        }

        // Review sources (per-element bind)
        var srcProdId   = qs('.cb-src-product-id', elEl);
        var srcProdName = qs('.cb-src-product-name', elEl);
        var srcCatId    = qs('.cb-src-category-id', elEl);
        var srcCatName  = qs('.cb-src-category-name', elEl);
        if (srcProdId)   data.params.source_product_id   = parseInt(srcProdId.value || 0, 10);
        if (srcProdName) data.params.source_product_name = srcProdName.value;
        if (srcCatId)    data.params.source_category_id   = parseInt(srcCatId.value || 0, 10);
        if (srcCatName)  data.params.source_category_name = srcCatName.value;

        // Autocomplete items
        var acVal  = qs('.cb-ac-val', elEl);
        var acName = qs('.cb-ac-name', elEl);
        var acImg  = qs('.cb-ac-img', elEl);
        if (acVal)  data.params.product_id   = data.params.article_id = data.params.category_id = parseInt(acVal.value || 0, 10);
        if (acName) data.params.product_name = data.params.article_name = data.params.category_name = acName.value;
        if (acImg)  data.params.product_img  = data.params.article_img  = data.params.category_img  = acImg.value;

        // Product card overrides — image/rating are language-independent (params),
        // name/description/pros/cons are multilang (collected into data[lid] below).
        var ovrImg  = qs('.cb-override-image-val', elEl);
        var ovrRate = qs('.cb-override-rating', elEl);
        if (ovrImg)  data.params.override_image  = ovrImg.value;
        if (ovrRate) data.params.override_rating = ovrRate.value;

        // Per-lang data
        cbLangs.forEach(function (lang) {
            var lid = lang.language_id;
            var ld  = {};

            // WYSIWYG / text content
            // After Jodit init the original div is replaced by a <textarea class="cb-wysiwyg" data-lang="X">
            // Jodit attaches itself as textarea.jodit — use getEditorValue() for current content
            var wysiwyg = qs('.cb-wysiwyg[data-lang="' + lid + '"]', elEl);
            if (wysiwyg) {
                if (wysiwyg.tagName === 'TEXTAREA' && wysiwyg.jodit) {
                    ld.content = wysiwyg.jodit.getEditorValue();
                } else if (wysiwyg.tagName === 'TEXTAREA') {
                    ld.content = wysiwyg.value;
                } else {
                    ld.content = wysiwyg.innerHTML;
                }
            }

            var htmlArea = qs('.cb-html-area[data-lang="' + lid + '"]', elEl);
            if (htmlArea) ld.content = htmlArea.value;

            var faqQ = qs('.cb-faq-question[data-lang="' + lid + '"]', elEl);
            var faqA = qs('.cb-faq-answer[data-lang="' + lid + '"]', elEl);
            if (faqQ) ld.question = faqQ.value;
            if (faqA) ld.answer   = faqA.value;

            var reviewText = qs('.cb-review-text[data-lang="' + lid + '"]', elEl);
            if (reviewText) ld.content = reviewText.value;

            // Image — usually per-language, but carousel_image stores a single
            // image shared across languages (only one .cb-image-val without data-lang).
            // Fallback to the shared input so the path is written into every lang slot.
            var imageVal = qs('.cb-image-val[data-lang="' + lid + '"]', elEl)
                        || qs('.cb-image-val:not([data-lang])', elEl);
            if (imageVal) ld.image = imageVal.value;
            var altInp = qs('.cb-image-alt[data-lang="' + lid + '"]', elEl);
            if (altInp) ld.alt = altInp.value;
            var titleInp = qs('.cb-image-title[data-lang="' + lid + '"]', elEl);
            if (titleInp) ld.title = titleInp.value;
            var urlInp = qs('.cb-image-url[data-lang="' + lid + '"]', elEl);
            if (urlInp) ld.url = urlInp.value;
            var wInp = qs('.cb-image-w[data-lang="' + lid + '"]', elEl);
            if (wInp) ld.width = wInp.value;
            var hInp = qs('.cb-image-h[data-lang="' + lid + '"]', elEl);
            if (hInp) ld.height = hInp.value;

            // Product card overrides — multilang (name/description/pros/cons)
            var ovrName = qs('.cb-override-name[data-lang="' + lid + '"]', elEl);
            if (ovrName) ld.override_name = ovrName.value;
            var ovrDesc = qs('.cb-override-description[data-lang="' + lid + '"]', elEl);
            if (ovrDesc) ld.override_description = ovrDesc.value;
            var ovrPros = qs('.cb-override-pros[data-lang="' + lid + '"]', elEl);
            if (ovrPros) ld.override_pros = ovrPros.value;
            var ovrCons = qs('.cb-override-cons[data-lang="' + lid + '"]', elEl);
            if (ovrCons) ld.override_cons = ovrCons.value;

            // Video
            var videoLink = qs('.cb-video-link', elEl);
            var videoId   = qs('.cb-video-id', elEl);
            var videoThumb = qs('.cb-video-thumb', elEl);
            if (videoLink) ld.url = videoLink.value;
            if (videoId)   ld.video_id = videoId.value;
            if (videoThumb && videoThumb.src) ld.thumb = videoThumb.src;
            var videoPoster = qs('.cb-video-poster-val', elEl);
            var videoLocal  = qs('.cb-video-local-val', elEl);
            if (videoPoster) ld.poster = videoPoster.value;
            if (videoLocal)  ld.local  = videoLocal.value;

            if (Object.keys(ld).length) data.data[lid] = ld;
        });

        return data;
    }

    function safeParseParams(str) {
        if (!str) return {};
        try { return JSON.parse(str); } catch (e) { return {}; }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Modal helpers
    // ─────────────────────────────────────────────────────────────────────────

    function openModal(id) {
        var el = qs('#' + id);
        if (el) show(el);
    }
    function closeModal(id) {
        var el = qs('#' + id);
        if (el) hide(el);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // WYSIWYG initialization
    // ─────────────────────────────────────────────────────────────────────────

    function initWysiwyg() {
        var edEl = qs('#cb-editor');
        if (!edEl) return;
        qsa('.cb-wysiwyg', edEl).forEach(initWysiwygEl);
    }

    function initWysiwygInEl(container) {
        qsa('.cb-wysiwyg', container).forEach(initWysiwygEl);
    }

    function initWysiwygEl(div) {
        if (div.dataset.wsInit) return;
        div.dataset.wsInit = '1';
        var wysiwyg = (window.cbI18n && window.cbI18n.wysiwyg) || (window.cbTypes && 'jodit') || 'jodit';
        // Jodit
        if (wysiwyg === 'jodit' && window.Jodit) {
            var textarea = document.createElement('textarea');
            textarea.value = div.innerHTML;
            // Preserve data-lang so extractElement selector can find it
            if (div.dataset.lang) textarea.dataset.lang = div.dataset.lang;
            textarea.className = 'cb-wysiwyg';
            textarea.dataset.wsInit = '1';
            div.parentNode.insertBefore(textarea, div);
            div.parentNode.removeChild(div);
            window.Jodit.make(textarea, { height: 200, toolbarButtonSize: 'small' });
        } else {
            // Plain contenteditable fallback
            div.contentEditable = 'true';
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Form Builder modal (form element)
    // ─────────────────────────────────────────────────────────────────────────

    var fbCurrentElEl = null;

    function initFormBuilder() {
        document.addEventListener('click', function (e) {
            // Open builder
            var btn = e.target.closest('.cb-btn-form-configure');
            if (btn) {
                var elEl = btn.closest('.cb-element');
                if (elEl) openFormBuilder(elEl);
                return;
            }

            // Modal tab switch (general / fields)
            var fbTab = e.target.closest('.cb-modal-tab[data-fb-tab]');
            if (fbTab) {
                var modal = fbTab.closest('#cb-modal-form-builder');
                if (!modal) return;
                qsa('.cb-modal-tab[data-fb-tab]', modal).forEach(function (t2) { t2.classList.remove('active'); });
                qsa('.cb-modal-tab-panel[data-fb-panel]', modal).forEach(function (p) {
                    p.classList.remove('active');
                    p.setAttribute('hidden', '');
                });
                fbTab.classList.add('active');
                var panel = qs('.cb-modal-tab-panel[data-fb-panel="' + fbTab.dataset.fbTab + '"]', modal);
                if (panel) { panel.classList.add('active'); panel.removeAttribute('hidden'); }
                return;
            }

            // Lang tab switch in builder (general tab multi-lang panels + per-field)
            var fbLangTab = e.target.closest('.fb-lang, .fb-field-lang');
            if (fbLangTab && fbLangTab.closest('#cb-modal-form-builder')) {
                var lid    = fbLangTab.dataset.langId;
                var scope  = fbLangTab.closest('.cb-lang-tabs').parentElement;
                var tabSel = fbLangTab.classList.contains('fb-lang') ? '.fb-lang' : '.fb-field-lang';
                var panSel = fbLangTab.classList.contains('fb-lang') ? '.fb-lang-panel' : '.fb-field-lang-panel';
                qsa(tabSel, scope).forEach(function (t2) { t2.classList.remove('active'); });
                qsa(panSel, scope).forEach(function (p) { p.classList.remove('active'); });
                fbLangTab.classList.add('active');
                var lp = qs(panSel + '[data-lang-id="' + lid + '"]', scope);
                if (lp) lp.classList.add('active');
                return;
            }

            // Add field
            if (e.target.closest('#fb-add-field')) {
                var list = qs('#fb-fields-list');
                if (list) {
                    list.appendChild(buildFieldRow({ type: 'text', name: '', required: 0, accept: '', lang: {} }));
                    if (window.lucide) window.lucide.createIcons();
                }
                return;
            }

            // Delete field
            var delBtn = e.target.closest('.fb-field-delete');
            if (delBtn) {
                var row = delBtn.closest('.fb-field-row');
                if (row) row.remove();
                return;
            }

            // Apply
            if (e.target.closest('#fb-save')) { saveFormBuilder(); return; }
        });

        // Field type change → toggle accept / options visibility
        document.addEventListener('change', function (e) {
            if (!e.target.classList.contains('fb-field-type')) return;
            var row = e.target.closest('.fb-field-row');
            if (row) applyFieldTypeUi(row, e.target.value);
        });
    }

    function openFormBuilder(elEl) {
        fbCurrentElEl = elEl;
        var modal = qs('#cb-modal-form-builder');
        if (!modal) return;

        var input  = qs('.cb-form-params-json', elEl);
        var params = {};
        if (input && input.value) { try { params = JSON.parse(input.value); } catch (e) {} }

        // General tab
        var rec = qs('#fb-recipient', modal); if (rec) rec.value = params.recipient_email || '';
        var rdr = qs('#fb-redirect',  modal); if (rdr) rdr.value = params.redirect_url    || '';
        var cap = qs('#fb-captcha',   modal); if (cap) cap.checked = !!params.captcha_enabled;

        var dataLang = params.lang || {};
        qsa('.fb-subject',      modal).forEach(function (i) { i.value = (dataLang[i.dataset.lang] || {}).subject         || ''; });
        qsa('.fb-submit-label', modal).forEach(function (i) { i.value = (dataLang[i.dataset.lang] || {}).submit_label    || ''; });
        qsa('.fb-success',      modal).forEach(function (i) { i.value = (dataLang[i.dataset.lang] || {}).success_message || ''; });

        // Reset to "general" tab
        qsa('.cb-modal-tab[data-fb-tab]', modal).forEach(function (t2) { t2.classList.toggle('active', t2.dataset.fbTab === 'general'); });
        qsa('.cb-modal-tab-panel[data-fb-panel]', modal).forEach(function (p) {
            var isGen = p.dataset.fbPanel === 'general';
            p.classList.toggle('active', isGen);
            if (isGen) p.removeAttribute('hidden'); else p.setAttribute('hidden', '');
        });

        // Fields list
        var list = qs('#fb-fields-list', modal);
        if (list) {
            list.innerHTML = '';
            (params.fields || []).forEach(function (f) { list.appendChild(buildFieldRow(f)); });
            if (window.Sortable && !list.dataset.sortableInit) {
                list.dataset.sortableInit = '1';
                new window.Sortable(list, { handle: '.fb-field-handle', animation: 150 });
            }
        }

        if (window.lucide) window.lucide.createIcons();
        openModal('cb-modal-form-builder');
    }

    function buildFieldRow(f) {
        f = f || {};
        var types = ['text','email','tel','number','textarea','select','radio','checkbox','file','image'];
        var langTabsHtml = '', langPanelsHtml = '';
        cbLangs.forEach(function (lang, idx) {
            var lid = lang.language_id;
            var ld  = (f.lang && f.lang[lid]) || {};
            var act = idx === 0 ? ' active' : '';
            langTabsHtml += '<button type="button" class="cb-lang-tab fb-field-lang' + act + '" data-lang-id="' + lid + '">'
                          + ((lang.code || '').toUpperCase().substr(0, 2)) + '</button>';
            langPanelsHtml +=
              '<div class="cb-lang-panel fb-field-lang-panel' + act + '" data-lang-id="' + lid + '">'
                + '<input type="text" class="ok-input ok-input-sm fb-field-label" data-lang="' + lid
                  + '" placeholder="' + (t.entry_field_label || 'Label') + '" value="' + esc(ld.label) + '">'
                + '<input type="text" class="ok-input ok-input-sm fb-field-placeholder" data-lang="' + lid
                  + '" placeholder="' + (t.entry_field_placeholder || 'Placeholder') + '" value="' + esc(ld.placeholder) + '">'
                + '<textarea class="ok-input ok-input-sm fb-field-options" data-lang="' + lid
                  + '" rows="2" placeholder="' + (t.entry_field_options || 'Options (one per line)') + '">'
                  + esc(ld.options) + '</textarea>'
              + '</div>';
        });

        var typeOpts = types.map(function (k) {
            var label = t['field_' + k] || k;
            return '<option value="' + k + '"' + (f.type === k ? ' selected' : '') + '>' + esc(label) + '</option>';
        }).join('');

        var row = document.createElement('div');
        row.className = 'fb-field-row';
        row.innerHTML =
          '<div class="fb-field-row-head">'
            + '<span class="fb-field-handle ok-handle" title="drag"><i data-lucide="menu"></i></span>'
            + '<select class="ok-input ok-input-sm fb-field-type" title="' + (t.entry_field_type || 'Type') + '">' + typeOpts + '</select>'
            + '<input type="text" class="ok-input ok-input-sm fb-field-name" placeholder="' + (t.entry_field_name || 'name') + '" value="' + esc(f.name) + '">'
            + '<label class="ok-toggle-wrap ok-toggle-sm" title="' + (t.entry_field_required || 'Required') + '">'
              + '<input type="checkbox" class="ok-toggle-input fb-field-required"' + (f.required ? ' checked' : '') + '>'
              + '<span class="ok-toggle-track"><span class="ok-toggle-thumb"></span></span>'
            + '</label>'
            + '<button type="button" class="ok-btn ok-btn-danger ok-btn-xs fb-field-delete" title="' + (t.button_delete || 'Delete') + '"><i data-lucide="trash-2"></i></button>'
          + '</div>'
          + (cbLangs.length > 1 ? '<div class="cb-lang-tabs fb-field-lang-tabs">' + langTabsHtml + '</div>' : '')
          + '<div class="fb-field-lang-panels">' + langPanelsHtml + '</div>';

        applyFieldTypeUi(row, f.type || 'text');
        return row;
    }

    function applyFieldTypeUi(row, type) {
        var hasOptions = (type === 'select' || type === 'radio' || type === 'checkbox');
        qsa('.fb-field-options', row).forEach(function (ta) { ta.style.display = hasOptions ? '' : 'none'; });
    }

    function saveFormBuilder() {
        if (!fbCurrentElEl) { closeModal('cb-modal-form-builder'); return; }
        var modal = qs('#cb-modal-form-builder');
        if (!modal) return;

        var params = {};
        var rec = qs('#fb-recipient', modal); params.recipient_email = rec ? rec.value : '';
        var rdr = qs('#fb-redirect',  modal); params.redirect_url    = rdr ? rdr.value : '';
        var cap = qs('#fb-captcha',   modal); params.captcha_enabled = (cap && cap.checked) ? 1 : 0;

        params.lang = {};
        qsa('.fb-lang-panel', modal).forEach(function (p) {
            var lid = p.dataset.langId;
            var s   = qs('.fb-subject',      p);
            var sl  = qs('.fb-submit-label', p);
            var sm  = qs('.fb-success',      p);
            params.lang[lid] = {
                subject:         s  ? s.value  : '',
                submit_label:    sl ? sl.value : '',
                success_message: sm ? sm.value : ''
            };
        });

        params.fields = qsa('#fb-fields-list .fb-field-row', modal).map(function (row) {
            var ty = qs('.fb-field-type',     row);
            var nm = qs('.fb-field-name',     row);
            var rq = qs('.fb-field-required', row);
            var f = {
                type:     ty ? ty.value : 'text',
                name:     nm ? nm.value : '',
                required: (rq && rq.checked) ? 1 : 0,
                lang:     {}
            };
            qsa('.fb-field-lang-panel', row).forEach(function (lp) {
                var lid = lp.dataset.langId;
                var lb  = qs('.fb-field-label',       lp);
                var ph  = qs('.fb-field-placeholder', lp);
                var op  = qs('.fb-field-options',     lp);
                f.lang[lid] = {
                    label:       lb ? lb.value : '',
                    placeholder: ph ? ph.value : '',
                    options:     op ? op.value : ''
                };
            });
            return f;
        });

        var input = qs('.cb-form-params-json', fbCurrentElEl);
        if (input) input.value = JSON.stringify(params);

        var badge = qs('.cb-form-field-count', fbCurrentElEl);
        if (badge) badge.textContent = String(params.fields.length);

        var hint = qs('.cb-form-element-body .ok-text-muted', fbCurrentElEl);
        if (hint) {
            hint.innerHTML = '';
            var ic = document.createElement('i');
            ic.setAttribute('data-lucide', params.recipient_email ? 'at-sign' : 'info');
            var sp = document.createElement('span');
            sp.textContent = ' ' + (params.recipient_email || (t.form_builder_no_recipient || ''));
            hint.appendChild(ic);
            hint.appendChild(sp);
            if (window.lucide) window.lucide.createIcons();
        }

        closeModal('cb-modal-form-builder');
        if (window.okNotify) window.okNotify(t.text_success || 'OK', 'success');
    }

    function esc(s) {
        if (s == null) return '';
        return String(s).replace(/[&<>"']/g, function (c) {
            return c === '&' ? '&amp;' : c === '<' ? '&lt;' : c === '>' ? '&gt;'
                 : c === '"' ? '&quot;' : '&#39;';
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Sortable initialization
    // ─────────────────────────────────────────────────────────────────────────

    function initSortable() {
        if (!window.Sortable) return;
        // Blocks list
        var blocksList = qs('#cb-blocks-list');
        if (blocksList) new window.Sortable(blocksList, { handle: '.cb-handle', animation: 150, draggable: '.cb-block' });

        // Existing rows/cols/elements
        qsa('[data-sortable]').forEach(function (el) {
            new window.Sortable(el, { handle: '.cb-handle', animation: 150 });
        });
    }

    function initSortableInEl(containerEl) {
        if (!window.Sortable) return;
        qsa('[data-sortable]', containerEl).forEach(function (el) {
            if (!el.dataset.sortableInit) {
                el.dataset.sortableInit = '1';
                new window.Sortable(el, { handle: '.cb-handle', animation: 150 });
            }
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // License — POSTs key to activate endpoint, redirects on success
    // ─────────────────────────────────────────────────────────────────────────

    function initLicense() {
        var btn = document.getElementById('cb-btn-activate');
        if (!btn) return;

        btn.addEventListener('click', function () {
            var inp = document.getElementById('cb-license-key');
            var msg = document.getElementById('cb-license-msg');
            var url = btn.dataset.url || '';
            var key = (inp || {}).value || '';

            if (!url) return;
            if (!key.trim()) {
                if (msg) { msg.textContent = (window.cbI18n && window.cbI18n.text_license_status_not_validated) || ''; msg.className = 'ok-help ok-text-danger ok-mb-3'; }
                return;
            }

            btn.disabled = true;
            if (msg) { msg.textContent = '…'; msg.className = 'ok-help ok-text-muted ok-mb-3'; }

            var fd = new FormData();
            fd.append('license_key', key);

            fetch(url, { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (j) {
                    btn.disabled = false;
                    if (j.success) {
                        if (msg) { msg.textContent = j.message || 'OK'; msg.className = 'ok-help ok-text-success ok-mb-3'; }
                        if (window.okNotify) window.okNotify(j.message || 'OK', 'success');
                        if (j.redirect_url) {
                            setTimeout(function () { window.location.href = j.redirect_url; }, 600);
                        }
                    } else {
                        if (msg) { msg.textContent = j.message || 'Error'; msg.className = 'ok-help ok-text-danger ok-mb-3'; }
                        if (window.okNotify) window.okNotify(j.message || 'Error', 'error');
                    }
                })
                .catch(function () {
                    btn.disabled = false;
                    if (msg) { msg.textContent = 'Network error'; msg.className = 'ok-help ok-text-danger ok-mb-3'; }
                });
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Boot
    // ─────────────────────────────────────────────────────────────────────────

    document.addEventListener('DOMContentLoaded', function () {
        // Re-read window globals (inline <script> runs before footer scripts)
        t        = window.cbI18n         || {};
        ts       = window.cbSettingsI18n || {};
        cbTypes  = window.cbTypes        || {};
        var rawLangs = window.cbLanguages || [];
        cbLangs = Array.isArray(rawLangs) ? rawLangs : Object.values(rawLangs);
        cbLangId = window.cbLanguageId   || 1;

        // Settings page URLs: read from data-attributes (HTML parser decodes &amp; → &)
        var dataEl = document.getElementById('cb-settings-data');
        if (dataEl) {
            urls = {
                save:        dataEl.dataset.saveUrl        || '',
                stickers:    dataEl.dataset.stickersUrl    || '',
                presets:     dataEl.dataset.presetsUrl     || '',
                migrate:     dataEl.dataset.migrateUrl     || '',
                demo:        dataEl.dataset.demoUrl        || '',
                submissions: dataEl.dataset.submissionsUrl || ''
            };
        }

        // Init Coloris color picker globally
        if (window.Coloris) {
            try {
                Coloris({ el: '.coloris', theme: 'default', format: 'hex', formatToggle: false });
            } catch (e) {}
        }

        initTabs();
        initSettingsSave();
        initStickers();
        initPresets();
        initMigration();
        initDemoPage();
        initSubmissions();
        initEditor();
        initImagePicker();
        initLicense();
        // Init Lucide icons
        if (window.lucide) window.lucide.createIcons();
    });

}());
