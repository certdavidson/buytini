// Cron Manager | © 2026 oc-kit.com | https://oc-kit.com
(function () {
    'use strict';

    // Читаємо конфіг всередині DOMContentLoaded — до цього моменту
    // inline <script> з window.cmUrls / window.cmI18n вже виконався
    var urls, i18n;
    var activeLogsJobId = 0;
    var scheduleTimer   = null;

    // ─── Init ────────────────────────────────────────────────────────────────

    document.addEventListener('DOMContentLoaded', function () {
        urls = window.cmUrls || {};
        i18n = window.cmI18n || {};
        lucide.createIcons();
        bindEvents();
    });

    function bindEvents() {
        on('#cm-btn-add',       'click', openAddModal);
        on('#cm-btn-add-empty', 'click', openAddModal);
        on('#cm-btn-scan',      'click', doScan);
        on('#cm-btn-save',      'click', saveJob);

        // Table row actions (delegation)
        document.addEventListener('click', function (e) {
            var btn;

            btn = e.target.closest('.cm-btn-edit');
            if (btn) { openEditModal(+btn.dataset.jobId); return; }

            btn = e.target.closest('.cm-btn-delete');
            if (btn) { deleteJob(+btn.dataset.jobId); return; }

            btn = e.target.closest('.cm-btn-run');
            if (btn) { runJob(+btn.dataset.jobId); return; }

            btn = e.target.closest('.cm-btn-logs');
            if (btn) { openLogs(+btn.dataset.jobId); return; }

            btn = e.target.closest('.cm-toggle');
            if (btn) { toggleJob(+btn.dataset.jobId, btn.checked); return; }

            // Scan: quick-add button
            btn = e.target.closest('.cm-scan-add-btn');
            if (btn) {
                openAddModalPrefill({ name: btn.dataset.name, command: btn.dataset.file, type: 'php' });
                closeModal('cm-modal-scan');
                return;
            }

            // Modal close: [data-modal-close] or backdrop click
            btn = e.target.closest('[data-modal-close]');
            if (btn) { closeModal(btn.dataset.modalClose); return; }

            if (e.target.classList.contains('ok-modal-backdrop')) {
                closeModal(e.target.id);
            }
        });

        on('#cm-form-schedule', 'input', function () {
            clearTimeout(scheduleTimer);
            scheduleTimer = setTimeout(previewSchedule, 600);
        });

        on('#cm-form-type', 'change', updateCommandHelp);
        on('#cm-btn-clear-logs', 'click', clearLogs);
    }

    // ─── Modal helpers ───────────────────────────────────────────────────────

    function openModal(id) {
        var el = document.getElementById(id);
        if (el) { el.removeAttribute('hidden'); lucide.createIcons(); }
    }

    function closeModal(id) {
        var el = document.getElementById(id);
        if (el) el.setAttribute('hidden', '');
    }

    // ─── Add / Edit modal ─────────────────────────────────────────────────────

    function openAddModal() {
        resetForm();
        qs('#cm-modal-form-title').textContent = i18n.button_add || 'Add Job';
        openModal('cm-modal-form');
    }

    function openAddModalPrefill(data) {
        resetForm();
        qs('#cm-modal-form-title').textContent = i18n.button_add || 'Add Job';
        if (data.name)    qs('#cm-form-name').value    = data.name;
        if (data.command) qs('#cm-form-command').value = data.command;
        if (data.type)    qs('#cm-form-type').value    = data.type;
        updateCommandHelp();
        openModal('cm-modal-form');
    }

    function openEditModal(jobId) {
        resetForm();
        qs('#cm-modal-form-title').textContent = i18n.button_edit || 'Edit';
        openModal('cm-modal-form');

        fetch(urls.getJob + '&job_id=' + jobId)
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!res.success || !res.job) { okNotify('Error loading job', 'error'); return; }
                var j = res.job;
                qs('#cm-form-job-id').value    = j.job_id;
                qs('#cm-form-name').value       = j.name;
                qs('#cm-form-desc').value       = j.description || '';
                qs('#cm-form-type').value       = j.type;
                qs('#cm-form-command').value    = j.command;
                qs('#cm-form-schedule').value   = j.schedule;
                qs('#cm-form-timeout').value    = j.timeout;
                qs('#cm-form-status').checked   = !!j.status;
                updateCommandHelp();
                previewSchedule();
            });
    }

    function resetForm() {
        qs('#cm-form-job-id').value    = '0';
        qs('#cm-form-name').value       = '';
        qs('#cm-form-desc').value       = '';
        qs('#cm-form-type').value       = 'php';
        qs('#cm-form-command').value    = '';
        qs('#cm-form-schedule').value   = '* * * * *';
        qs('#cm-form-timeout').value    = '60';
        qs('#cm-form-status').checked   = true;
        qs('#cm-schedule-preview').textContent = '';
        updateCommandHelp();
    }

    function updateCommandHelp() {
        var type = qs('#cm-form-type').value;
        var map  = { php: i18n.help_command_php, shell: i18n.help_command_shell, url: i18n.help_command_url };
        qs('#cm-command-help').innerHTML = map[type] || '';
    }

    // ─── Schedule preview ────────────────────────────────────────────────────

    function previewSchedule() {
        var schedule = qs('#cm-form-schedule').value.trim();
        var preview  = qs('#cm-schedule-preview');
        if (!schedule) { preview.textContent = ''; return; }

        fetch(urls.previewSchedule + '&schedule=' + encodeURIComponent(schedule))
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.valid) {
                    preview.textContent    = (i18n.text_schedule_next || 'Next:') + ' ' + res.label;
                    preview.style.color    = '';
                } else {
                    preview.textContent    = i18n.text_schedule_invalid || 'Invalid';
                    preview.style.color    = '#e74c3c';
                }
            });
    }

    // ─── Save job ────────────────────────────────────────────────────────────

    function saveJob() {
        var data = okFormData(qs('#cm-form'));
        if (!qs('#cm-form-status').checked) data['status'] = '0';

        var btn = qs('#cm-btn-save');
        btn.disabled = true;

        okPost(urls.save, data, function (res) {
            btn.disabled = false;
            if (res.error) { okNotify(res.error, 'error'); return; }
            okNotify(res.success || i18n.text_success, 'success');
            closeModal('cm-modal-form');
            if (res.job) upsertRow(res.job);
        }, function () {
            btn.disabled = false;
            okNotify('Network error', 'error');
        });
    }

    // ─── Delete ──────────────────────────────────────────────────────────────

    function deleteJob(jobId) {
        if (!confirm(i18n.text_confirm_delete || 'Delete?')) return;
        okPost(urls.delete, { job_id: jobId }, function (res) {
            if (res.error) { okNotify(res.error, 'error'); return; }
            var row = qs('#cm-row-' + jobId);
            if (row) row.remove();
            updateBadge();
            okNotify(res.success || i18n.text_success_delete, 'success');
            var tbody = qs('#cm-jobs-table tbody');
            if (tbody && tbody.rows.length === 0) location.reload();
        });
    }

    // ─── Toggle ──────────────────────────────────────────────────────────────

    function toggleJob(jobId, status) {
        okPost(urls.toggle, { job_id: jobId, status: status ? '1' : '0' }, function (res) {
            if (res.error) {
                okNotify(res.error, 'error');
                var chk = qs('#cm-row-' + jobId + ' .cm-toggle');
                if (chk) chk.checked = !status;
            }
        });
    }

    // ─── Run job ─────────────────────────────────────────────────────────────

    function runJob(jobId) {
        if (!confirm(i18n.text_confirm_run || 'Run now?')) return;

        showEl('#cm-run-spinner');
        hideEl('#cm-run-result');
        openModal('cm-modal-run');

        okPost(urls.run, { job_id: jobId }, function (res) {
            hideEl('#cm-run-spinner');
            showEl('#cm-run-result');
            lucide.createIcons();

            var badge = qs('#cm-run-status-badge');
            badge.className   = res.success ? 'ok-badge ok-badge-success-soft' : 'ok-badge ok-badge-danger-soft';
            badge.textContent = res.success
                ? (i18n.text_status_success || 'Success')
                : (i18n.text_status_error   || 'Error');

            var dur = res.duration || 0;
            qs('#cm-run-duration').textContent = dur >= 1000
                ? (dur / 1000).toFixed(1) + ' ' + (i18n.text_sec || 'sec')
                : dur + ' ' + (i18n.text_ms || 'ms');

            qs('#cm-run-output').textContent = res.output || '';
            if (res.job) updateRowStatus(res.job);
        }, function () {
            closeModal('cm-modal-run');
            okNotify('Network error', 'error');
        });
    }

    // ─── Logs ────────────────────────────────────────────────────────────────

    function openLogs(jobId) {
        activeLogsJobId = jobId;
        var row  = qs('#cm-row-' + jobId);
        var name = row ? ((row.querySelector('.cm-job-name') || {}).textContent || '') : '#' + jobId;
        qs('#cm-logs-title').textContent = name;

        qs('#cm-logs-tbody').innerHTML = '';
        showEl('#cm-logs-loading');
        hideEl('#cm-logs-content');

        openModal('cm-modal-logs');

        fetch(urls.logs + '&job_id=' + jobId)
            .then(function (r) { return r.json(); })
            .then(function (res) {
                hideEl('#cm-logs-loading');
                showEl('#cm-logs-content');
                lucide.createIcons();

                if (!res.success || !res.logs || !res.logs.length) {
                    showEl('#cm-logs-empty');
                    return;
                }
                hideEl('#cm-logs-empty');

                qs('#cm-logs-tbody').innerHTML = res.logs.map(renderLogRow).join('');
            })
            .catch(function () {
                hideEl('#cm-logs-loading');
                showEl('#cm-logs-content');
                showEl('#cm-logs-empty');
            });
    }

    function renderLogRow(log) {
        var badge = log.status === 'success'
            ? '<span class="ok-badge ok-badge-success-soft">' + escHtml(i18n.text_status_success || 'OK') + '</span>'
            : '<span class="ok-badge ok-badge-danger-soft">'  + escHtml(i18n.text_status_error   || 'Err') + '</span>';

        var dur = log.duration != null
            ? (log.duration >= 1000 ? (log.duration/1000).toFixed(1)+' '+(i18n.text_sec||'sec') : log.duration+' '+(i18n.text_ms||'ms'))
            : '—';

        var trig = log.triggered_by === 'manual'
            ? (i18n.text_triggered_manual    || 'Manual')
            : (i18n.text_triggered_scheduler || 'Scheduler');

        var out = log.output ? escHtml(log.output.substring(0, 300)) + (log.output.length > 300 ? '…' : '') : '—';

        return '<tr>'
            + '<td class="ok-mono ok-text-sm">' + escHtml(log.started_at) + '</td>'
            + '<td class="ok-text-sm">' + escHtml(dur) + '</td>'
            + '<td>' + badge + '</td>'
            + '<td class="ok-text-sm ok-muted">' + escHtml(trig) + '</td>'
            + '<td><pre class="ok-pre-inline">' + out + '</pre></td>'
            + '</tr>';
    }

    function clearLogs() {
        if (!activeLogsJobId) return;
        okPost(urls.clearLogs, { job_id: activeLogsJobId }, function () {
            qs('#cm-logs-tbody').innerHTML = '';
            showEl('#cm-logs-empty');
        });
    }

    // ─── Scan ────────────────────────────────────────────────────────────────

    function doScan() {
        qs('#cm-scan-body').innerHTML = '<div class="ok-empty-state ok-py-md"><div class="ok-spinner"></div></div>';
        openModal('cm-modal-scan');

        fetch(urls.scan)
            .then(function (r) { return r.json(); })
            .then(function (res) {
                lucide.createIcons();
                if (!res.success || !res.files || !res.files.length) {
                    qs('#cm-scan-body').innerHTML = '<p class="ok-muted ok-text-center ok-py-md">' + escHtml(i18n.text_scan_none || 'No new files.') + '</p>';
                    return;
                }
                var html = '<ul class="ok-scan-list">';
                res.files.forEach(function (f) {
                    html += '<li class="ok-scan-item">'
                        + '<span class="ok-mono ok-text-sm ok-flex-1">' + escHtml(f.file) + '</span>'
                        + '<button type="button" class="ok-btn ok-btn-primary ok-btn-xs cm-scan-add-btn"'
                        + ' data-file="' + escAttr(f.command) + '" data-name="' + escAttr(f.name) + '">'
                        + '<i data-lucide="plus"></i>'
                        + '</button></li>';
                });
                html += '</ul>';
                qs('#cm-scan-body').innerHTML = html;
                lucide.createIcons();
            });
    }

    // ─── Table update helpers ─────────────────────────────────────────────────

    function upsertRow(job) {
        if (qs('#cm-row-' + job.job_id)) {
            updateRowStatus(job);
            var nameEl = qs('#cm-row-' + job.job_id + ' .cm-job-name');
            if (nameEl) nameEl.textContent = job.name;
        } else {
            location.reload();
            return;
        }
        updateBadge();
    }

    function updateRowStatus(job) {
        var row = qs('#cm-row-' + job.job_id);
        if (!row) return;

        var cells = row.cells;
        if (cells[6]) cells[6].innerHTML = statusBadgeHtml(job.last_status);
        if (cells[4] && job.last_run) {
            cells[4].innerHTML = '<span class="ok-text-sm">' + escHtml(job.last_run) + '</span>'
                + (job.last_duration ? '<div class="ok-muted ok-text-sm">' + job.last_duration + ' мс</div>' : '');
        }
        var nextLabel = row.querySelector('.cm-next-label');
        if (nextLabel) nextLabel.textContent = job.next_run_label || '—';
        lucide.createIcons();
    }

    function statusBadgeHtml(status) {
        var map = {
            success: ['ok-badge-success-soft', i18n.text_status_success || 'Success'],
            error:   ['ok-badge-danger-soft',  i18n.text_status_error   || 'Error'],
            running: ['ok-badge-info-soft',    i18n.text_status_running || 'Running'],
            never:   ['ok-badge-muted',        i18n.text_status_never   || 'Never'],
        };
        var p = map[status] || map.never;
        return '<span class="ok-badge ' + p[0] + '">' + escHtml(p[1]) + '</span>';
    }

    function updateBadge() {
        var badge = qs('#cm-total-badge');
        var tbody = qs('#cm-jobs-table tbody');
        if (badge && tbody) badge.textContent = tbody.rows.length;
    }

    // ─── Utils ───────────────────────────────────────────────────────────────

    function qs(sel)      { return document.querySelector(sel); }
    function on(sel, e, fn) { var el = qs(sel); if (el) el.addEventListener(e, fn); }
    function showEl(sel)  { var el = qs(sel); if (el) el.removeAttribute('hidden'); }
    function hideEl(sel)  { var el = qs(sel); if (el) el.setAttribute('hidden', ''); }

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function escAttr(s) {
        return String(s).replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }

}());
