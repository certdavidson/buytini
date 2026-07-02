/**
 * Easy Login — Admin JS
 * © 2026 oc-kit.com | https://oc-kit.com
 *
 * Requires: admin/view/javascript/ockit/assets/js/ok-common.js
 *   — okNotify(), initTabs()
 */
(function () {
    'use strict';

    var I18n = window.elI18n || {};

    // ── AJAX save settings ────────────────────────────────────────────────────

    function initSave() {
        var btn = document.getElementById('btn-save');
        if (!btn) return;

        btn.addEventListener('click', function () {
            var form = document.getElementById('form-el');
            if (!form) return;

            var data = new FormData(form);

            btn.disabled = true;
            fetch(form.action, { method: 'POST', body: data })
                .then(function (r) { return r.json(); })
                .then(function (json) {
                    btn.disabled = false;
                    if (json && json.success) {
                        if (window.okNotify) {
                            window.okNotify(json.success, 'success');
                        }
                    } else {
                        if (window.okNotify) {
                            window.okNotify((json && json.error) || I18n.error_save, 'error');
                        }
                    }
                })
                .catch(function () {
                    btn.disabled = false;
                    if (window.okNotify) {
                        window.okNotify(I18n.error_network, 'error');
                    }
                });
        });
    }

    // ── Log: clear all / clear old ───────────────────────────────────────────

    function initLogActions() {
        document.addEventListener('click', function (e) {
            var clearAll = e.target.closest('#btn-clear-log');
            var clearOld = e.target.closest('#btn-clear-old');

            if (clearAll) {
                if (!confirm(I18n.confirm_clear_log)) return;
                postEmpty(clearAll.dataset.url, clearAll.dataset.csrf, function () { location.reload(); });
            }
            if (clearOld) {
                if (!confirm(I18n.confirm_clear_old)) return;
                postEmpty(clearOld.dataset.url, clearOld.dataset.csrf, function () { location.reload(); });
            }
        });
    }

    function postEmpty(url, csrf, onDone) {
        var fd = new FormData();
        if (csrf) fd.append('csrf', csrf);
        fetch(url, { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (json) {
                if (json && json.success && window.okNotify) {
                    window.okNotify(json.success, 'success');
                } else if (json && json.error && window.okNotify) {
                    window.okNotify(json.error, 'error');
                }
                if (onDone) setTimeout(onDone, 600);
            })
            .catch(function () {
                if (window.okNotify) window.okNotify(I18n.error_network, 'error');
            });
    }

    // ── Tabs ──────────────────────────────────────────────────────────────────

    function initTabsLocal() {
        if (typeof window.initTabs === 'function') {
            window.initTabs('#el-layout-sidebar', '.ok-tabs-sidebar-item', '.ok-layout-panel');
        }
    }

    // ── Lucide icons ──────────────────────────────────────────────────────────

    function initLucide() {
        if (typeof window.lucide !== 'undefined' && window.lucide.createIcons) {
            window.lucide.createIcons();
        }
    }

    // ── License activation ────────────────────────────────────────────────────

    function initLicense() {
        var btn = document.getElementById('el-btn-activate');
        if (!btn) return;
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var keyEl = document.getElementById('el-license-key');
            var key   = (keyEl && keyEl.value || '').trim();
            if (!key) {
                if (window.okNotify) window.okNotify(I18n.error_license_key_required || 'License key required', 'error');
                if (keyEl) keyEl.focus();
                return;
            }
            if (!btn.dataset.url) {
                if (window.okNotify) window.okNotify(I18n.error_no_activate_url || 'No activation URL set', 'error');
                return;
            }
            btn.disabled = true;
            var origText = btn.innerHTML;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> ...';

            var body = new FormData();
            body.append('license_key', key);
            fetch(btn.dataset.url, { method: 'POST', body: body, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (json) {
                    btn.disabled = false;
                    btn.innerHTML = origText;
                    if (window.lucide && window.lucide.createIcons) window.lucide.createIcons();
                    if (json && json.success) {
                        if (window.okNotify) window.okNotify(json.message || 'OK', 'success');
                        if (json.redirect_url) {
                            setTimeout(function () { location.href = json.redirect_url; }, 800);
                        }
                    } else {
                        if (window.okNotify) window.okNotify((json && json.message) || I18n.error_save || 'Error', 'error');
                    }
                })
                .catch(function (err) {
                    btn.disabled = false;
                    btn.innerHTML = origText;
                    if (window.lucide && window.lucide.createIcons) window.lucide.createIcons();
                    if (window.okNotify) window.okNotify(I18n.error_network || ('Network error: ' + err.message), 'error');
                });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        I18n = window.elI18n || {};
        initSave();
        initLogActions();
        initLicense();
        initTabsLocal();
        initLucide();
    });

})();
