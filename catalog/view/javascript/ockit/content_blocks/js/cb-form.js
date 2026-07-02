/* Content Blocks Pro — Form submit handler
   © 2026 oc-kit.com | https://oc-kit.com
   Vanilla JS, no jQuery dependency. */
(function () {
    'use strict';
    if (window.cbFormBound) return;
    window.cbFormBound = true;

    function getSubmitUrl(form) {
        return form.dataset.submitUrl || window.cbFormSubmitUrl ||
            'index.php?route=extension/module/oc_kit_content_blocks/form_submit';
    }

    function $(sel, root) { return (root || document).querySelector(sel); }
    function $$(sel, root) { return Array.from((root || document).querySelectorAll(sel)); }

    function clearErrors(form) {
        $$('.cb-form-field', form).forEach(function (f) {
            f.classList.remove('cb-form-field--invalid');
            var err = $('.cb-form-error', f);
            if (err) { err.textContent = ''; err.hidden = true; }
        });
        var msg = $('.cb-form-message', form);
        if (msg) { msg.textContent = ''; msg.hidden = true; msg.classList.remove('is-success', 'is-error'); }
    }

    function setFieldError(form, fieldName, text) {
        var input = $('[name="' + fieldName + '"]', form);
        if (!input) return;
        var fieldEl = input.closest('.cb-form-field');
        if (!fieldEl) return;
        fieldEl.classList.add('cb-form-field--invalid');
        var err = $('.cb-form-error', fieldEl);
        if (err) { err.textContent = text; err.hidden = false; }
    }

    function clientValidate(form) {
        clearErrors(form);
        var ok = true;
        var maxBytes = parseInt(form.dataset.maxSize || '0', 10);
        $$('.cb-form-field', form).forEach(function (fieldEl) {
            var input = $('input, select, textarea', fieldEl);
            if (!input) return;
            var name = input.name;
            if (input.hasAttribute('required')) {
                var v = (input.type === 'checkbox' || input.type === 'radio')
                    ? !!$$('[name="' + name + '"]:checked', form).length
                    : (input.value || '').trim().length > 0;
                if (!v && input.type !== 'file') {
                    setFieldError(form, name, 'Required'); ok = false;
                }
            }
            if (input.type === 'email' && input.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value)) {
                setFieldError(form, name, 'Invalid email'); ok = false;
            }
            if (input.type === 'file' && input.files && input.files.length) {
                for (var i = 0; i < input.files.length; i++) {
                    if (maxBytes && input.files[i].size > maxBytes) {
                        setFieldError(form, name, 'File too large'); ok = false; break;
                    }
                }
            }
            if (input.type === 'file' && input.hasAttribute('required') && (!input.files || !input.files.length)) {
                setFieldError(form, name, 'Required'); ok = false;
            }
        });
        return ok;
    }

    // File-input: show filename + image preview
    document.addEventListener('change', function (e) {
        var inp = e.target;
        if (!inp.matches || !inp.matches('.cb-form-file-wrap input[type="file"]')) return;
        var wrap = inp.closest('.cb-form-file-wrap');
        if (!wrap) return;
        var nameEl = $('.cb-form-file-name', wrap);
        var preview = $('.cb-form-file-preview', wrap);
        var file = inp.files && inp.files[0];
        if (nameEl) nameEl.textContent = file ? file.name : '';
        if (preview) {
            preview.classList.remove('has');
            preview.innerHTML = '';
            if (file && /^image\//.test(file.type)) {
                var img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                preview.appendChild(img);
                preview.classList.add('has');
            }
        }
    });

    document.addEventListener('submit', function (e) {
        var form = e.target.closest('.cb-form');
        if (!form) return;
        e.preventDefault();
        if (!clientValidate(form)) return;

        var submitBtn = $('.cb-form-submit', form);
        var txt = $('.cb-form-submit-text', form);
        var loader = $('.cb-form-submit-loader', form);
        if (submitBtn) submitBtn.disabled = true;
        if (txt) txt.hidden = true;
        if (loader) loader.hidden = false;

        var fd = new FormData(form);
        fd.append('element_id', form.dataset.elementId || '');

        var xhr = new XMLHttpRequest();
        xhr.open('POST', getSubmitUrl(form), true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4) return;
            if (submitBtn) submitBtn.disabled = false;
            if (txt) txt.hidden = false;
            if (loader) loader.hidden = true;

            var resp = {};
            try { resp = JSON.parse(xhr.responseText); } catch (err) {}

            var msg = $('.cb-form-message', form);
            if (resp && resp.success) {
                if (msg) {
                    msg.textContent = resp.message || '';
                    msg.classList.add('is-success'); msg.hidden = false;
                }
                form.reset();
                $$('.cb-form-file-name', form).forEach(function (el) { el.textContent = ''; });
                $$('.cb-form-file-preview', form).forEach(function (el) {
                    el.classList.remove('has'); el.innerHTML = '';
                });
                if (resp.redirect) setTimeout(function () { window.location.href = resp.redirect; }, 800);
            } else {
                if (resp.errors) {
                    Object.keys(resp.errors).forEach(function (name) {
                        setFieldError(form, name, resp.errors[name]);
                    });
                }
                if (msg) {
                    msg.textContent = (resp && resp.message) || 'Error';
                    msg.classList.add('is-error'); msg.hidden = false;
                }
            }
        };
        xhr.send(fd);
    });
})();
