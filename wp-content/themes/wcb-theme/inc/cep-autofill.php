<?php
/**
 * WCB Theme — CEP Auto-fill via ViaCEP
 * Suporta checkout Classic e WooCommerce Blocks.
 * Aplica máscara 00000-000 e preenche endereço, cidade e estado.
 *
 * @package WCB_Theme
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function wcb_cep_autofill_script() {
    if ( ! is_singular( 'cartflows_step' ) && ! is_checkout() ) return;
    ?>
    <style>
    .wcb-cep-status {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 12px;
        font-weight: 500;
        margin-top: 6px;
        padding: 4px 10px;
        border-radius: 20px;
        transition: all 0.25s ease;
        line-height: 1.4;
    }
    .wcb-cep-status--loading { background: #EEF3FF; color: #155DFD; }
    .wcb-cep-status--success { background: #E6FAF4; color: #00A06A; }
    .wcb-cep-status--error   { background: #FEE2E2; color: #EF4444; }
    .wcb-cep-status--warn    { background: #FEF3C7; color: #B45309; }
    </style>
    <script>
    (function(){
        'use strict';

        /* ── Seletores: suporta Classic (#billing_postcode) e Blocks (#billing-postcode) ── */
        var SELECTORS = [
            '#billing_postcode',   /* WooCommerce Classic */
            '#billing-postcode',   /* WooCommerce Blocks  */
        ];

        var FIELD_MAP_CLASSIC = {
            street:     'billing_address_1',
            complement: 'billing_address_2',
            city:       'billing_city',
            state:      'billing_state',
        };

        var FIELD_MAP_BLOCKS = {
            street:     'billing-address_1',
            complement: 'billing-address_2',
            city:       'billing-city',
            state:      'billing-state',
        };

        /* ── Status badge ── */
        function createStatus(anchor) {
            var s = document.createElement('span');
            s.className = 'wcb-cep-status';
            s.style.display = 'none';
            if (anchor && anchor.parentNode) {
                anchor.parentNode.insertBefore(s, anchor.nextSibling);
            }
            return s;
        }

        function showStatus(el, type, text) {
            el.className = 'wcb-cep-status wcb-cep-status--' + type;
            el.textContent = text;
            el.style.display = 'inline-flex';
        }

        function hideStatus(el, delay) {
            setTimeout(function() { el.style.display = 'none'; }, delay || 0);
        }

        /* ── Máscara 00000-000 ── */
        function maskCep(value) {
            var digits = value.replace(/\D/g, '').slice(0, 8);
            if (digits.length > 5) return digits.slice(0, 5) + '-' + digits.slice(5);
            return digits;
        }

        /* ── Preencher campo (input ou select) ── */
        function fillField(id, value, isSelect) {
            var el = document.getElementById(id);
            if (!el) return;
            if (isSelect) {
                for (var i = 0; i < el.options.length; i++) {
                    if (el.options[i].value === value || el.options[i].text === value) {
                        el.selectedIndex = i;
                        el.dispatchEvent(new Event('change', { bubbles: true }));
                        break;
                    }
                }
            } else {
                el.value = value;
                el.dispatchEvent(new Event('input',  { bubbles: true }));
                el.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        /* ── Buscar CEP na API ViaCEP ── */
        function lookupCep(cep, fieldMap, statusEl) {
            showStatus(statusEl, 'loading', '⏳ Buscando endereço...');

            fetch('https://viacep.com.br/ws/' + cep + '/json/')
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    if (d.erro) {
                        showStatus(statusEl, 'error', '❌ CEP não encontrado');
                        hideStatus(statusEl, 3000);
                        return;
                    }

                    var street = (d.logradouro || '') +
                                 (d.complemento ? ', ' + d.complemento : '');

                    fillField(fieldMap.street,     street,        false);
                    fillField(fieldMap.complement, d.bairro || '', false);
                    fillField(fieldMap.city,       d.localidade || '', false);
                    fillField(fieldMap.state,      d.uf || '',    true);

                    showStatus(statusEl, 'success', '✅ Endereço preenchido!');
                    hideStatus(statusEl, 3000);
                })
                .catch(function() {
                    showStatus(statusEl, 'warn', '⚠️ Erro ao buscar CEP. Tente novamente.');
                    hideStatus(statusEl, 4000);
                });
        }

        /* ── Inicializar listener em um campo ── */
        function initField(el, fieldMap) {
            var statusEl = createStatus(el);
            var timer;

            /* Máscara ao digitar */
            el.addEventListener('input', function() {
                var masked = maskCep(this.value);
                if (this.value !== masked) this.value = masked;

                clearTimeout(timer);
                var digits = masked.replace(/\D/g, '');
                if (digits.length < 8) {
                    hideStatus(statusEl);
                    return;
                }

                timer = setTimeout(function() {
                    lookupCep(digits, fieldMap, statusEl);
                }, 600);
            });

            /* Também dispara ao colar (paste) */
            el.addEventListener('paste', function() {
                var self = this;
                setTimeout(function() {
                    var digits = self.value.replace(/\D/g, '');
                    if (digits.length === 8) lookupCep(digits, fieldMap, statusEl);
                }, 50);
            });
        }

        /* ── Bootstrap: tenta imediatamente e via MutationObserver para Blocks ── */
        function bootstrap() {
            SELECTORS.forEach(function(selector, idx) {
                var el = document.querySelector(selector);
                if (el && !el.dataset.wcbCep) {
                    el.dataset.wcbCep = '1';
                    initField(el, idx === 0 ? FIELD_MAP_CLASSIC : FIELD_MAP_BLOCKS);
                }
            });
        }

        /* MutationObserver para WooCommerce Blocks (renderiza depois do DOMContentLoaded) */
        var observer = new MutationObserver(function() { bootstrap(); });
        observer.observe(document.body, { childList: true, subtree: true });

        document.addEventListener('DOMContentLoaded', function() {
            bootstrap();
            /* Parar observer após 20s (Blocks já carregou) */
            setTimeout(function() { observer.disconnect(); }, 20000);
        });
    })();
    </script>
    <?php
}
add_action( 'wp_footer', 'wcb_cep_autofill_script', 5 );
