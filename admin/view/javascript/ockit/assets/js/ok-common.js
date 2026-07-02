// oc-kit Common Utils | © 2026 oc-kit.com | https://oc-kit.com
(function () {
    'use strict';

    // ─── Toast notification ──────────────────────────────────────────────────────
    // okNotify(msg, type)  type: success | error | warning | info

    window.okNotify = function (msg, type) {
        var el = document.createElement('div');
        el.className = 'ok-toast ok-toast-' + (type || 'success');
        el.textContent = msg;
        document.body.appendChild(el);
        setTimeout(function () {
            el.style.transition = 'opacity .3s';
            el.style.opacity    = '0';
            setTimeout(function () { el.remove(); }, 350);
        }, 2500);
    };

    // ─── Tab switching (sidebar layout) ─────────────────────────────────────────
    // initTabs(containerSel, itemSel, panelSel)
    // Scopes to each container independently.

    window.initTabs = function (containerSel, itemSel, panelSel) {
        document.querySelectorAll(containerSel).forEach(function (container) {
            container.querySelectorAll(itemSel).forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var targetId = btn.getAttribute('data-tab');
                    if (!targetId) return;
                    container.querySelectorAll(itemSel).forEach(function (b) { b.classList.remove('active'); });
                    btn.classList.add('active');
                    var scope = container.closest('.oc-kit') || container.parentElement;
                    scope.querySelectorAll(panelSel).forEach(function (p) { p.classList.remove('active'); });
                    var target = document.getElementById(targetId);
                    if (target) target.classList.add('active');
                });
            });
        });
    };

    // ─── Language sub-tabs ───────────────────────────────────────────────────────
    // Pattern: .ok-lang-group > .ok-lang-tabs-item[data-lang] + .ok-lang-panel[data-lang]
    // Global delegation — works for any module using this pattern.

    document.addEventListener('click', function (e) {
        var ltab = e.target.closest('.ok-lang-tabs-item');
        if (!ltab) return;

        var group = ltab.closest('.ok-lang-group');
        if (!group) return;

        var lang = ltab.dataset.lang;

        group.querySelectorAll('.ok-lang-tabs-item').forEach(function (t) {
            t.classList.toggle('active', t === ltab);
        });
        group.querySelectorAll('.ok-lang-panel').forEach(function (p) {
            p.classList.toggle('active', p.dataset.lang === lang);
        });
    });

    // ─── Copy to clipboard ───────────────────────────────────────────────────────
    // Pattern: <button class="ok-copy-btn" data-target="elementId" data-notify="Copied!">
    // Copies the value (input/textarea) or textContent of the target element.
    // Shows a checkmark for 1.5s after copy. Shows okNotify if data-notify is set.

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.ok-copy-btn');
        if (!btn) return;

        var target = document.getElementById(btn.dataset.target);
        if (!target) return;

        var text    = target.value || target.textContent;
        var notify  = btn.dataset.notify || '';
        var orig    = btn.innerHTML;

        function onCopied() {
            btn.innerHTML = '<i data-lucide="check"></i>';
            btn.classList.add('copied');
            if (window.lucide && window.lucide.createIcons) window.lucide.createIcons();
            setTimeout(function () { btn.innerHTML = orig; btn.classList.remove('copied'); if (window.lucide && window.lucide.createIcons) window.lucide.createIcons(); }, 1500);
            if (notify) window.okNotify(notify, 'success');
        }

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(onCopied);
        } else {
            var ta = document.createElement('textarea');
            ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
            document.body.appendChild(ta); ta.focus(); ta.select();
            document.execCommand('copy'); ta.remove();
            onCopied();
        }
    });

    // ─── okCopyCode — imperative copy with optional notify ───────────────────────
    // window.okCopyCode(text, btn, notifyMsg?)
    // Useful when you need to trigger copy programmatically (not via ok-copy-btn pattern).

    window.okCopyCode = function (text, btn, notifyMsg) {
        function onCopied() {
            if (btn) {
                btn.classList.add('copied');
                setTimeout(function () { btn.classList.remove('copied'); }, 1800);
            }
            if (notifyMsg) window.okNotify(notifyMsg, 'success');
        }
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(onCopied);
        } else {
            var ta = document.createElement('textarea');
            ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
            document.body.appendChild(ta); ta.focus(); ta.select();
            document.execCommand('copy'); ta.remove();
            onCopied();
        }
    };

    // ─── Form data collector ─────────────────────────────────────────────────────
    // okFormData(form) → plain object {name: value}
    //   - checkbox / radio: included only when checked
    //   - hidden inputs with same name as checkbox provide the "0" fallback
    //   - last value wins for duplicate names (hidden → then checked checkbox)

    window.okFormData = function (form) {
        var data = {};
        form.querySelectorAll('input, select, textarea').forEach(function (el) {
            if (!el.name) return;
            if (el.type === 'checkbox' || el.type === 'radio') {
                if (el.checked) data[el.name] = el.value;
            } else {
                data[el.name] = el.value;
            }
        });
        return data;
    };

    // ─── Dropdown panel helper ───────────────────────────────────────────────────
    // okDropdown(triggerSelector, panelSelector)
    //   Toggles a panel's visibility on trigger click and closes when clicking outside.
    //   Panel must use the `.ps-hidden` class to be hidden by default.

    window.okDropdown = function (triggerSelector, panelSelector) {
        var trigger = typeof triggerSelector === 'string' ? document.querySelector(triggerSelector) : triggerSelector;
        var panel   = typeof panelSelector === 'string'   ? document.querySelector(panelSelector)   : panelSelector;
        if (!trigger || !panel) return;

        // Support both `ok-hidden` (shared styleguide) and legacy `ps-hidden`
        var hiddenClass = panel.classList.contains('ok-hidden') ? 'ok-hidden' : 'ps-hidden';

        trigger.addEventListener('click', function (e) {
            e.stopPropagation();
            panel.classList.toggle(hiddenClass);
        });

        document.addEventListener('click', function (e) {
            if (panel.classList.contains(hiddenClass)) return;
            if (panel.contains(e.target) || trigger.contains(e.target)) return;
            panel.classList.add(hiddenClass);
        });
    };

    // ─── Bulk selection helper ───────────────────────────────────────────────────
    // okBulkSelection({ checkAll, rowCheck, bulkBtns, count?, rowSelectedClass? })
    //   checkAll          — selector for "select all" checkbox (e.g. '#ps-check-all')
    //   rowCheck          — selector for row checkboxes        (e.g. '.ps-row-check')
    //   bulkBtns          — selector for action buttons        (e.g. '.ps-bulk-btn')
    //   count             — optional selector for text node showing count
    //   rowSelectedClass  — optional CSS class added to <tr> when its checkbox is checked
    // Returns { getSelectedIds(), update() }.

    window.okBulkSelection = function (config) {
        var checkAll = document.querySelector(config.checkAll);
        var bulkBtns = document.querySelectorAll(config.bulkBtns);
        var countEl  = config.count ? document.querySelector(config.count) : null;
        var rowCls   = config.rowSelectedClass || '';

        function getSelectedIds() {
            var boxes = document.querySelectorAll(config.rowCheck + ':checked');
            var ids = [];
            for (var i = 0; i < boxes.length; i++) ids.push(boxes[i].value);
            return ids;
        }

        function update() {
            var ids = getSelectedIds();
            if (countEl) countEl.textContent = ids.length;
            var disabled = ids.length === 0;
            for (var i = 0; i < bulkBtns.length; i++) bulkBtns[i].disabled = disabled;
            if (checkAll) {
                var all = document.querySelectorAll(config.rowCheck);
                checkAll.checked       = all.length > 0 && ids.length === all.length;
                checkAll.indeterminate = ids.length > 0 && ids.length < all.length;
            }
        }

        function syncRowHighlight(box) {
            if (!rowCls) return;
            var row = box.closest('tr');
            if (row) row.classList.toggle(rowCls, box.checked);
        }

        if (checkAll) {
            checkAll.addEventListener('change', function () {
                var boxes = document.querySelectorAll(config.rowCheck);
                for (var i = 0; i < boxes.length; i++) {
                    boxes[i].checked = checkAll.checked;
                    syncRowHighlight(boxes[i]);
                }
                update();
            });
        }

        document.addEventListener('change', function (e) {
            if (!e.target || !e.target.matches || !e.target.matches(config.rowCheck)) return;
            syncRowHighlight(e.target);
            update();
        });

        return { getSelectedIds: getSelectedIds, update: update };
    };

    // ─── Generic POST helper ─────────────────────────────────────────────────────
    // okPost(url, body, onSuccess, onError)
    //   body      — plain object, passed as URLSearchParams
    //   onSuccess — function(data) called with parsed JSON
    //   onError   — optional function() called on network/parse error

    window.okPost = function (url, body, onSuccess, onError) {
        fetch(url, {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    new URLSearchParams(body)
        })
            .then(function (r) { return r.json(); })
            .then(onSuccess)
            .catch(function () {
                if (typeof onError === 'function') onError();
            });
    };

}());
