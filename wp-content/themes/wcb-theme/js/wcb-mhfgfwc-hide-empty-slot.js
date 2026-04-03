/**
 * Oculta #mhfgfwc-blocks-slot quando .mhfgfwc-blocks-slot__inner não tem conteúdo útil
 * (plugin MH Free Gifts for WooCommerce — carrinho/checkout em blocos).
 */
(function () {
    'use strict';

    var SLOT_ID = 'mhfgfwc-blocks-slot';
    var INNER_SEL = '.mhfgfwc-blocks-slot__inner';
    var EMPTY_CLASS = 'wcb-mhfgfwc-slot--empty';
    var emptyMarkTimer = null;
    var EMPTY_MARK_DELAY_MS = 280;

    function slotHasContent(slot) {
        if (!slot) {
            return false;
        }
        var inner = slot.querySelector(INNER_SEL);
        if (!inner) {
            return false;
        }
        if (inner.children.length > 0) {
            return true;
        }
        var t = (inner.textContent || '').replace(/\u00a0/g, ' ').trim();
        return t.length > 0;
    }

    function update() {
        var slot = document.getElementById(SLOT_ID);
        if (!slot) {
            return;
        }
        var has = slotHasContent(slot);
        if (has) {
            if (emptyMarkTimer) {
                clearTimeout(emptyMarkTimer);
                emptyMarkTimer = null;
            }
            slot.classList.remove(EMPTY_CLASS);
            slot.removeAttribute('aria-hidden');
        } else {
            if (emptyMarkTimer) {
                clearTimeout(emptyMarkTimer);
            }
            emptyMarkTimer = setTimeout(function () {
                emptyMarkTimer = null;
                var s = document.getElementById(SLOT_ID);
                if (!s) {
                    return;
                }
                if (!slotHasContent(s)) {
                    s.classList.add(EMPTY_CLASS);
                    s.setAttribute('aria-hidden', 'true');
                }
            }, EMPTY_MARK_DELAY_MS);
        }
    }

    /** Observa o inner atual (recriado quando o Blocks re-renderiza). */
    function wireInnerObserver() {
        var inner = document.querySelector('#' + SLOT_ID + ' ' + INNER_SEL);
        if (!inner || inner._wcbMhfgfwcMo) {
            return;
        }
        inner._wcbMhfgfwcMo = true;
        var mo = new MutationObserver(function () {
            update();
        });
        mo.observe(inner, { childList: true, subtree: true, characterData: true });
    }

    function tick() {
        update();
        wireInnerObserver();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', tick);
    } else {
        tick();
    }

    document.addEventListener('mhfgfwc:cartChanged', function () {
        requestAnimationFrame(tick);
    });
})();
