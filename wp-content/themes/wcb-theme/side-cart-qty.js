/**
 * WCB Side Cart — Quantity Stepper (v2 — UI Otimista)
 * Atualiza o número instantaneamente (optimistic UI) e sincroniza
 * com o servidor em background. Sem spinner de modal — só o item.
 *
 * Debug no browser (tempo ida/volta do AJAX): localStorage.setItem('wcb_cart_debug','1')
 * Desligar: localStorage.removeItem('wcb_cart_debug')
 */
jQuery(document).ready(function ($) {

    var $modal = $('.xoo-wsc-modal');

    // Usa o mesmo helper de URL do plugin
    function getWcUrl(endpoint) {
        if (typeof xoo_wsc_params === 'undefined') return '';
        return xoo_wsc_params.wc_ajax_url.toString().replace('%%endpoint%%', endpoint);
    }

    // Mapa de timers de debounce por cart_key
    var _debounceTimers = {};
    // Mapa de qty "otimista" por cart_key (valor projetado ainda não confirmado)
    var _pendingQty = {};

    /**
     * Sincroniza a quantidade com o servidor (chamada real ao AJAX).
     * Garante que apenas UMA requisição por item rode por vez.
     */
    function syncWithServer($stepper, cartKey, targetQty) {
        // Marca o stepper como "sincronizando" (loading sutil apenas no item)
        $stepper.addClass('wcb-qty-syncing');
        $stepper.attr('aria-busy', 'true');

        var _wcbAjaxT0 = (window.performance && performance.now) ? performance.now() : Date.now();
        var _wcbDbg = typeof window.localStorage !== 'undefined' && localStorage.getItem('wcb_cart_debug') === '1';

        $.ajax({
            url: getWcUrl('xoo_wsc_update_item_quantity'),
            type: 'POST',
            data: {
                cart_key: cartKey,
                qty: targetQty,
                container: 'cart',
                isCart: xoo_wsc_params.isCart == '1',
                isCheckout: xoo_wsc_params.isCheckout == '1'
            },
            success: function (response) {
                if (response && response.fragments) {
                    // Aplica os fragments atualizados (recalcula totais e re-renderiza)
                    $.each(response.fragments, function (key, value) {
                        $(key).replaceWith(value);
                    });
                    $(document.body).trigger('wc_fragments_refreshed');
                    $(document.body).trigger('xoo_wsc_quantity_updated', [response]);
                    $(document.body).trigger('xoo_wsc_cart_updated', [response]);
                }
                // Limpa o qty pendente após confirmação do servidor
                delete _pendingQty[cartKey];
            },
            error: function () {
                // Em caso de erro: reverte o número exibido para o valor real
                delete _pendingQty[cartKey];
                var $s = $modal.find('[data-key="' + cartKey + '"]');
                if ($s.length) {
                    var realQty = parseInt($s.data('qty'), 10) || 1;
                    $s.find('.wcb-qty-value').text(realQty);
                }
            },
            complete: function () {
                if (_wcbDbg) {
                    var _t1 = (window.performance && performance.now) ? performance.now() : Date.now();
                    console.log('[WCB cart] AJAX xoo_wsc_update_item_quantity round-trip: ' + Math.round(_t1 - _wcbAjaxT0) + ' ms (rede+servidor+DOM jQuery.replaceWith nos fragments)');
                }
                $modal.find('.wcb-qty-stepper[data-key="' + cartKey + '"]').removeClass('wcb-qty-syncing').attr('aria-busy', 'false');
            }
        });
    }

    /**
     * Atualiza UI imediatamente (optimistic) e agenda sync com servidor.
     * Clicks múltiplos rápidos são agrupados em 1 único request (debounce 200ms).
     */
    function updateQty($stepper, cartKey, newQty) {
        if (!cartKey || newQty === undefined || newQty < 0) return;

        // ── 1. Atualiza a UI na hora (sem esperar servidor) ──────────────
        _pendingQty[cartKey] = newQty;
        $stepper.find('.wcb-qty-value').text(newQty);

        // Desabilita os botões temporariamente para evitar double-click acidental
        $stepper.find('.wcb-qty-btn').prop('disabled', true);
        setTimeout(function () {
            $stepper.find('.wcb-qty-btn').prop('disabled', false);
        }, 280);

        // ── 2. Debounce: agrupa clicks rápidos em 1 único request ─────────
        clearTimeout(_debounceTimers[cartKey]);
        _debounceTimers[cartKey] = setTimeout(function () {
            var finalQty = _pendingQty[cartKey];
            if (finalQty === undefined) return;
            syncWithServer($stepper, cartKey, finalQty);
        }, 200);
    }

    // Delegação de eventos no modal (funciona após fragment refresh)
    $modal.on('click', '.wcb-qty-plus', function (e) {
        e.stopPropagation();
        var $stepper = $(this).closest('.wcb-qty-stepper');
        var cartKey = $stepper.data('key');
        // Usa qty pendente (otimista) se existir, senão pega do data attribute
        var currentQty = _pendingQty[cartKey] !== undefined
            ? _pendingQty[cartKey]
            : (parseInt($stepper.data('qty'), 10) || 1);
        updateQty($stepper, cartKey, currentQty + 1);
    });

    // Remover com teclado (Enter / Espaço) — span com role="button"
    $modal.on('keydown', '.xoo-wsc-smr-del[tabindex]', function (e) {
        if (e.key !== 'Enter' && e.key !== ' ') return;
        e.preventDefault();
        $(this).trigger('click');
    });

    $modal.on('click', '.wcb-qty-minus', function (e) {
        e.stopPropagation();
        var $stepper = $(this).closest('.wcb-qty-stepper');
        var cartKey = $stepper.data('key');
        var currentQty = _pendingQty[cartKey] !== undefined
            ? _pendingQty[cartKey]
            : (parseInt($stepper.data('qty'), 10) || 1);
        // qty 0 = remover item (comportamento nativo do plugin)
        updateQty($stepper, cartKey, Math.max(0, currentQty - 1));
    });

});

/**
 * WCB Side Cart — DOM Reorder (Image Left)
 * Garante que a imagem do produto fique como primeiro filho do card,
 * forçando a posição visual à esquerda independente da ordem do plugin.
 */
jQuery(document).ready(function ($) {
    function reorderCardImages() {
        $('.xoo-wsc-product').each(function () {
            var $product = $(this);
            var $img = $product.find('.xoo-wsc-img-col').first();
            if ($img.length && $product.children().first().get(0) !== $img.get(0)) {
                $product.prepend($img);
            }
        });
    }

    /* Uma passagem por frame — evita dezenas de reorder por burst de mutações do Xoo. */
    var reorderRaf = null;
    function scheduleReorderCardImages() {
        if (reorderRaf !== null) {
            return;
        }
        reorderRaf = window.requestAnimationFrame(function () {
            reorderRaf = null;
            reorderCardImages();
        });
    }

    function mutationsMightAffectProducts(mutations) {
        var i, j, n;
        for (i = 0; i < mutations.length; i++) {
            var nodes = mutations[i].addedNodes;
            for (j = 0; j < nodes.length; j++) {
                n = nodes[j];
                if (n.nodeType !== 1) {
                    continue;
                }
                if (n.classList && n.classList.contains('xoo-wsc-product')) {
                    return true;
                }
                if (n.querySelector && n.querySelector('.xoo-wsc-product')) {
                    return true;
                }
            }
        }
        return false;
    }

    $(document.body).on('xoo_wsc_cart_updated wc_fragments_refreshed xoo_wsc_cart_loaded', scheduleReorderCardImages);
    $(document).on('xoo_wsc_show_cart', scheduleReorderCardImages);

    var observer = new MutationObserver(function (mutations) {
        if (!mutationsMightAffectProducts(mutations)) {
            return;
        }
        scheduleReorderCardImages();
    });
    var $body = document.querySelector('.xoo-wsc-body');
    if ($body) {
        observer.observe($body, { childList: true, subtree: true });
    }
    window.requestAnimationFrame(function () {
        scheduleReorderCardImages();
    });
});
