/**
 * WCB — Age gate: cookie, scroll unlock, focus trap (sem fechar com Esc ou clique fora).
 */
(function () {
    'use strict';

    var root = document.getElementById('wcb-age-gate');
    if (!root || !window.wcbAgeGate) return;

    var cfg = window.wcbAgeGate;
    var cookieName = cfg.cookieName || 'wcb_age_verified';
    var maxAge = parseInt(cfg.maxAgeSeconds, 10) || 7776000;
    var exitUrl = cfg.exitUrl || 'https://www.google.com/';

    function getFocusable() {
        var sel = 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])';
        return Array.prototype.slice.call(root.querySelectorAll(sel)).filter(function (el) {
            return el.offsetParent !== null || el === document.activeElement;
        });
    }

    function trapKey(e) {
        if (e.key !== 'Tab') return;
        var list = getFocusable();
        if (list.length === 0) return;
        var first = list[0];
        var last = list[list.length - 1];
        if (e.shiftKey) {
            if (document.activeElement === first) {
                e.preventDefault();
                last.focus();
            }
        } else {
            if (document.activeElement === last) {
                e.preventDefault();
                first.focus();
            }
        }
    }

    function setVerifiedCookie() {
        var secure = window.location.protocol === 'https:' ? '; Secure' : '';
        document.cookie =
            encodeURIComponent(cookieName) +
            '=1; path=/; max-age=' +
            maxAge +
            '; SameSite=Lax' +
            secure;
    }

    function dismiss() {
        root.setAttribute('hidden', '');
        document.body.classList.remove('wcb-age-gate--active');
        document.removeEventListener('keydown', trapKey, true);
    }

    var btnYes = document.getElementById('wcb-age-gate-confirm');
    var btnNo = document.getElementById('wcb-age-gate-decline');
    if (btnYes) {
        btnYes.addEventListener('click', function () {
            setVerifiedCookie();
            dismiss();
        });
    }
    if (btnNo) {
        btnNo.addEventListener('click', function () {
            window.location.href = exitUrl;
        });
    }

    root.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            e.preventDefault();
            e.stopPropagation();
        }
    });

    document.addEventListener('keydown', trapKey, true);

    var focusables = getFocusable();
    if (focusables.length) {
        focusables[0].focus();
    }
})();
