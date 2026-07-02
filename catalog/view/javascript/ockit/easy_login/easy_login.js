/**
 * Easy Login — Catalog JS
 * © 2026 oc-kit.com | https://oc-kit.com
 */
(function () {
    'use strict';

    function t(key) {
        return (window.elI18n && window.elI18n[key]) || '';
    }

    function postJson(url, data) {
        var body = new URLSearchParams();
        Object.keys(data).forEach(function (k) { body.append(k, data[k] == null ? '' : data[k]); });
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body
        }).then(function (r) { return r.json(); });
    }

    function getReturnUrl() {
        var box = document.querySelector('.el-buttons[data-el-return]');
        return (box && box.dataset.elReturn) || '';
    }

    function getLinkMode() {
        var box = document.querySelector('.el-buttons[data-el-link]');
        return box && box.dataset.elLink === '1' ? '1' : '';
    }

    function setMsg(box, text, type) {
        if (!box) return;
        box.textContent = text || '';
        box.classList.remove('is-error', 'is-success');
        if (type) box.classList.add('is-' + type);
    }

    // ─── Magic link ────────────────────────────────────────────────────────────

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('#el-magic-send');
        if (!btn) return;
        var emailEl = document.getElementById('el-magic-email');
        var msg     = document.getElementById('el-magic-msg');
        var email   = (emailEl && emailEl.value || '').trim();
        if (!email) { setMsg(msg, t('error_email_required'), 'error'); return; }
        btn.disabled = true;
        postJson(btn.dataset.url, { email: email, redirect: getReturnUrl(), link: getLinkMode() })
            .then(function (json) {
                btn.disabled = false;
                setMsg(msg, json.message || t('success_magic_sent'), 'success');
            })
            .catch(function () {
                btn.disabled = false;
                setMsg(msg, t('error_network'), 'error');
            });
    });

    // ─── SMS OTP ───────────────────────────────────────────────────────────────

    var smsState = { phone: '' };

    document.addEventListener('click', function (e) {
        var sendBtn = e.target.closest('#el-sms-send');
        var verBtn  = e.target.closest('#el-sms-verify');
        var box     = document.querySelector('.el-sms');
        if (!box) return;
        var stepPhone = box.querySelector('.el-sms-step-phone');
        var stepCode  = box.querySelector('.el-sms-step-code');
        var msg       = document.getElementById('el-sms-msg');

        if (sendBtn) {
            var phone = (document.getElementById('el-sms-phone') || {}).value || '';
            if (!phone) { setMsg(msg, t('error_phone_required'), 'error'); return; }
            sendBtn.disabled = true;
            postJson(sendBtn.dataset.url, { phone: phone, redirect: getReturnUrl(), link: getLinkMode() })
                .then(function (json) {
                    sendBtn.disabled = false;
                    if (json.success) {
                        smsState.phone = json.phone || phone;
                        if (stepPhone) stepPhone.hidden = true;
                        if (stepCode)  stepCode.hidden  = false;
                        setMsg(msg, t('success_code_sent'), 'success');
                    } else {
                        setMsg(msg, json.message || t('error_send_failed'), 'error');
                    }
                })
                .catch(function () { sendBtn.disabled = false; setMsg(msg, t('error_network'), 'error'); });
        }

        if (verBtn) {
            var code = (document.getElementById('el-sms-code') || {}).value || '';
            if (!code) { setMsg(msg, t('error_code_required'), 'error'); return; }
            verBtn.disabled = true;
            postJson(verBtn.dataset.url, { phone: smsState.phone, code: code })
                .then(function (json) {
                    verBtn.disabled = false;
                    if (json.success && json.redirect_url) {
                        location.href = json.redirect_url;
                    } else {
                        setMsg(msg, json.message || t('error_invalid_code'), 'error');
                    }
                })
                .catch(function () { verBtn.disabled = false; setMsg(msg, t('error_network'), 'error'); });
        }
    });

    // ─── SMS code: auto-submit when full length reached ───────────────────────

    document.addEventListener('input', function (e) {
        var input = e.target;
        if (!input || input.id !== 'el-sms-code') return;
        var len = parseInt(input.getAttribute('maxlength'), 10) || 0;
        var digits = (input.value || '').replace(/\D/g, '');
        // Strip non-digits live
        if (digits !== input.value) input.value = digits;
        if (len > 0 && digits.length >= len) {
            var box = input.closest('.el-sms');
            var verBtn = box ? box.querySelector('#el-sms-verify') : document.getElementById('el-sms-verify');
            if (verBtn && !verBtn.disabled) verBtn.click();
        }
    });

    // ─── Phone mask: +380 XX XXX XX XX ────────────────────────────────────────

    function formatPhone(digits) {
        if (digits.indexOf('380') !== 0) {
            if (digits.length && digits[0] === '0') digits = '380' + digits.substring(1);
            else if (digits.length && digits[0] !== '3') digits = '380' + digits;
        }
        digits = digits.substring(0, 12);
        var out = '+380';
        if (digits.length > 3)  out += ' ' + digits.substring(3, 5);
        if (digits.length > 5)  out += ' ' + digits.substring(5, 8);
        if (digits.length > 8)  out += ' ' + digits.substring(8, 10);
        if (digits.length > 10) out += ' ' + digits.substring(10, 12);
        return out;
    }

    function applyPhoneMask(input) {
        if (input._maskInited) return;
        input._maskInited = true;

        input.addEventListener('input', function () {
            var digits = (input.value || '').replace(/\D/g, '');
            input.value = digits ? formatPhone(digits) : '';
        });
        input.addEventListener('focus', function () {
            if (!input.value) input.value = '+380 ';
        });
        input.addEventListener('blur', function () {
            if (input.value === '+380' || input.value === '+380 ') input.value = '';
        });
        input.addEventListener('keydown', function (e) {
            // Block deleting the +380 prefix
            if ((e.key === 'Backspace' || e.key === 'Delete') && (input.value || '').replace(/\D/g, '').length <= 3) {
                e.preventDefault();
            }
        });
    }

    function initPhoneMasks() {
        document.querySelectorAll('.el-phone-mask').forEach(applyPhoneMask);
    }

    // ─── Telegram widget ───────────────────────────────────────────────────────

    function initTelegram() {
        document.querySelectorAll('.el-btn-telegram-wrap').forEach(function (wrap) {
            if (wrap._inited) return;
            var bot      = wrap.dataset.elTgBot || '';
            var size     = wrap.dataset.elTgSize || 'large';
            var phone    = wrap.dataset.elTgPhone === '1';
            var callback = wrap.dataset.elTgCallback || '';
            if (!bot || !callback) return;

            window.elTelegramAuth = function (user) {
                user.redirect = getReturnUrl();
                user.link = getLinkMode();
                postJson(callback, user).then(function (json) {
                    if (json.success && json.redirect_url) location.href = json.redirect_url;
                });
            };

            var s = document.createElement('script');
            s.async = true;
            s.src = 'https://telegram.org/js/telegram-widget.js?22';
            s.setAttribute('data-telegram-login', bot);
            s.setAttribute('data-size', size);
            if (phone) s.setAttribute('data-userpic', 'true');
            s.setAttribute('data-onauth', 'elTelegramAuth(user)');
            // Telegram only supports 'write' for data-request-access; only set it
            // when we actually need to ask for it (i.e. phone-request mode).
            if (phone) s.setAttribute('data-request-access', 'write');
            wrap.appendChild(s);
            wrap._inited = true;
        });
    }

    // ─── Account: unlink ───────────────────────────────────────────────────────

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.el-unlink');
        if (!btn) return;
        if (!confirm(btn.dataset.confirm || t('confirm_unlink'))) return;
        var box  = btn.closest('.el-linked');
        var csrf = (box && box.dataset.csrf) || '';
        postJson(btn.dataset.url, { identity_id: btn.dataset.id, csrf: csrf })
            .then(function (json) {
                if (json.success) {
                    var item = btn.closest('.el-linked-item');
                    if (item) item.remove();
                }
            });
    });

    // ─── Login error banner from callback redirects ───────────────────────────
    // Catalog OAuth callbacks redirect back to /account/login?el_login_error=...
    // when authentication is refused (provider linked elsewhere, or unverified
    // email matches an existing customer). Surface a localized banner above
    // the login form so the user understands why the flow failed.

    function showLoginError(reason) {
        var key = 'error_login_' + reason;
        var msg = t(key);
        if (!msg) return;
        var div = document.createElement('div');
        div.className = 'el-login-banner is-error';
        div.textContent = msg;
        // Preferred anchors → fall back to a top-of-body floating banner so the
        // user still sees the error on themes that lack #content / haven't
        // injected .el-buttons.
        var anchor = document.querySelector('.el-buttons')
            || document.querySelector('#content form')
            || document.querySelector('#content');
        if (anchor && anchor.parentNode) {
            anchor.parentNode.insertBefore(div, anchor);
            return;
        }
        div.classList.add('is-floating');
        (document.body || document.documentElement).insertBefore(div, document.body ? document.body.firstChild : null);
    }

    function consumeLoginErrorParam() {
        try {
            var url = new URL(window.location.href);
            var reason = url.searchParams.get('el_login_error');
            if (!reason) return;
            if (reason !== 'conflict' && reason !== 'needs_confirmation') return;
            showLoginError(reason);
            // Strip the param so refresh doesn't re-show it.
            url.searchParams.delete('el_login_error');
            window.history.replaceState({}, '', url.toString());
        } catch (e) { /* old browsers — no-op */ }
    }

    document.addEventListener('DOMContentLoaded', function () {
        initTelegram();
        initPhoneMasks();
        consumeLoginErrorParam();
    });

})();
