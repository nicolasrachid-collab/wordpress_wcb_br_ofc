/**
 * WCB — Swatches, preço dinâmico (variação), steppers de quantidade: PDP + Quick View.
 *
 * @package WCB_Theme
 */
(function (window, $) {
    'use strict';

    function formatMoneyBr(v) {
        var n = typeof v === 'number' ? v : parseFloat(v);
        if (isNaN(n)) {
            n = 0;
        }
        return n.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function pdpPriceIds() {
        return {
            block: 'wcb-pdp-price-block',
            current: 'wcb-pdp-price-current',
            old: 'wcb-pdp-price-old',
            pixWrap: 'wcb-pdp-pix',
            pixValue: 'wcb-pdp-pix-value',
            economize: 'wcb-pdp-economize-pix',
            discount: 'wcb-pdp-discount'
        };
    }

    function qvPriceIds() {
        return {
            block: 'wcb-qv-pdp-price-block',
            current: 'wcb-qv-pdp-price-current',
            old: 'wcb-qv-pdp-price-old',
            pixWrap: 'wcb-qv-pdp-pix',
            pixValue: 'wcb-qv-pdp-pix-value',
            economize: 'wcb-qv-pdp-economize-pix',
            discount: 'wcb-qv-pdp-discount'
        };
    }

    function pdpSubtotalIds() {
        return {
            root: 'wcb-pdp-subtotal',
            price: 'wcb-pdp-subtotal-price',
            qty: 'wcb-pdp-subtotal-qty',
            pix: 'wcb-pdp-subtotal-pix-val'
        };
    }

    function qvSubtotalIds() {
        return {
            root: 'wcb-qv-pdp-subtotal',
            price: 'wcb-qv-pdp-subtotal-price',
            qty: 'wcb-qv-pdp-subtotal-qty',
            pix: 'wcb-qv-pdp-subtotal-pix-val'
        };
    }

    function applyVariationToPriceIds(ids, variation) {
        var priceBlock = document.getElementById(ids.block);
        if (!priceBlock || !variation) {
            return;
        }
        var currentEl = document.getElementById(ids.current);
        var oldEl = document.getElementById(ids.old);
        var pixEl = document.getElementById(ids.pixValue);
        var economizeEl = document.getElementById(ids.economize);
        var discEl = document.getElementById(ids.discount);
        var pixWrap = document.getElementById(ids.pixWrap);
        var price = parseFloat(variation.display_price) || 0;
        var regular = parseFloat(variation.display_regular_price) || 0;
        var pix = price * 0.95;
        if (currentEl) {
            currentEl.textContent = 'R$ ' + formatMoneyBr(price);
        }
        if (pixEl) {
            pixEl.textContent = 'R$ ' + formatMoneyBr(pix);
        }
        if (economizeEl) {
            if (price > 0) {
                economizeEl.textContent = 'Economia de R$ ' + formatMoneyBr(price - pix) + ' no pagamento à vista';
                economizeEl.style.display = '';
            } else {
                economizeEl.style.display = 'none';
            }
        }
        if (oldEl) {
            if (regular > price && regular > 0) {
                oldEl.textContent = 'De R$ ' + formatMoneyBr(regular);
                oldEl.style.display = '';
            } else {
                oldEl.style.display = 'none';
            }
        }
        if (discEl) {
            if (regular > price && regular > 0) {
                var pct = Math.round(((regular - price) / regular) * 100);
                discEl.textContent = '\u2212' + pct + '% OFF';
                discEl.style.display = '';
            } else {
                discEl.style.display = 'none';
            }
        }
        if (pixWrap) {
            pixWrap.style.display = price > 0 ? '' : 'none';
        }
    }

    function resetPriceIdsToBase(ids) {
        var priceBlock = document.getElementById(ids.block);
        if (!priceBlock) {
            return;
        }
        var base = parseFloat(priceBlock.getAttribute('data-base-price')) || 0;
        var baseReg = parseFloat(priceBlock.getAttribute('data-base-regular')) || 0;
        var pix = base * 0.95;
        var currentEl = document.getElementById(ids.current);
        var oldEl = document.getElementById(ids.old);
        var pixEl = document.getElementById(ids.pixValue);
        var economizeEl = document.getElementById(ids.economize);
        var discEl = document.getElementById(ids.discount);
        var pixWrap = document.getElementById(ids.pixWrap);
        if (currentEl) {
            currentEl.textContent = 'R$ ' + formatMoneyBr(base);
        }
        if (pixEl) {
            pixEl.textContent = 'R$ ' + formatMoneyBr(pix);
        }
        if (economizeEl) {
            if (base > 0) {
                economizeEl.textContent = 'Economia de R$ ' + formatMoneyBr(base - pix) + ' no pagamento à vista';
                economizeEl.style.display = '';
            } else {
                economizeEl.style.display = 'none';
            }
        }
        if (oldEl) {
            if (baseReg > base && baseReg > 0) {
                oldEl.textContent = 'De R$ ' + formatMoneyBr(baseReg);
                oldEl.style.display = '';
            } else {
                oldEl.style.display = 'none';
            }
        }
        if (discEl) {
            if (baseReg > base && baseReg > 0) {
                var pctb = Math.round(((baseReg - base) / baseReg) * 100);
                discEl.textContent = '\u2212' + pctb + '% OFF';
                discEl.style.display = '';
            } else {
                discEl.style.display = 'none';
            }
        }
        if (pixWrap) {
            pixWrap.style.display = base > 0 ? '' : 'none';
        }
    }

    function updateSubtotal(form, subIds, priceKey) {
        subIds = subIds || pdpSubtotalIds();
        priceKey = priceKey || 'wcbCurrentVariationPrice';
        var subtotalEl = document.getElementById(subIds.root);
        if (!subtotalEl) {
            return;
        }
        var unitPrice = window[priceKey] || 0;
        var qtyInput = form ? form.querySelector('input.qty') : null;
        var qty = qtyInput ? parseInt(qtyInput.value, 10) || 1 : 1;
        if (unitPrice <= 0) {
            subtotalEl.style.display = 'none';
            return;
        }
        var total = unitPrice * qty;
        var totalPix = total * 0.95;
        var priceEl = document.getElementById(subIds.price);
        var qtyEl = document.getElementById(subIds.qty);
        var pixEl = document.getElementById(subIds.pix);
        if (priceEl) {
            priceEl.textContent = 'R$ ' + total.toFixed(2).replace('.', ',');
        }
        if (qtyEl) {
            qtyEl.textContent = '(' + qty + (qty === 1 ? ' item' : ' itens') + ')';
        }
        if (pixEl) {
            pixEl.textContent = 'R$ ' + totalPix.toFixed(2).replace('.', ',') + ' no PIX (5% off)';
        }
        subtotalEl.style.display = '';
    }

    function initBuyboxQtySteppers(scope) {
        var root = scope || document;
        var inputs = root.querySelectorAll('.wcb-pdp-buybox__form .quantity input.qty');
        inputs.forEach(function (input) {
            var parent = input.parentElement;
            if (!parent || parent.querySelector('.wcb-qty-btn')) {
                return;
            }
            var existingBtns = parent.querySelectorAll('button:not(.wcb-qty-btn), .minus, .plus');
            existingBtns.forEach(function (b) {
                b.style.display = 'none';
            });
            var minusBtn = document.createElement('button');
            minusBtn.type = 'button';
            minusBtn.className = 'wcb-qty-btn wcb-qty-minus';
            minusBtn.innerHTML = '\u2212';
            minusBtn.setAttribute('aria-label', 'Diminuir quantidade');
            var plusBtn = document.createElement('button');
            plusBtn.type = 'button';
            plusBtn.className = 'wcb-qty-btn wcb-qty-plus';
            plusBtn.innerHTML = '+';
            plusBtn.setAttribute('aria-label', 'Aumentar quantidade');
            parent.insertBefore(minusBtn, input);
            parent.appendChild(plusBtn);
            minusBtn.addEventListener('click', function () {
                var val = parseInt(input.value, 10) || 1;
                var min = parseInt(input.getAttribute('min'), 10) || 1;
                if (val > min) {
                    input.value = val - 1;
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
            plusBtn.addEventListener('click', function () {
                var val = parseInt(input.value, 10) || 1;
                var max = parseInt(input.getAttribute('max'), 10) || 9999;
                if (val < max) {
                    input.value = val + 1;
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        });
    }

    function wcbSwatchEscapeUrl(url) {
        if (!url || typeof url !== 'string') {
            return '';
        }
        return url.replace(/\\/g, '/').replace(/"/g, '%22').replace(/\(/g, '%28').replace(/\)/g, '%29');
    }

    function initSwatchCards(root, form, metaSelector, ns, clickInitialIfSelected) {
        if (!form || !$) {
            return;
        }
        var rows = form.querySelectorAll('.variations tr');
        var swatchMeta = {};
        var metaEl = root.querySelector ? root.querySelector(metaSelector) : document.querySelector(metaSelector);
        if (metaEl && metaEl.textContent) {
            try {
                swatchMeta = JSON.parse(metaEl.textContent);
            } catch (eMeta) {
                swatchMeta = {};
            }
        }

        function getSwatchVisualMeta(selectEl, slug) {
            var an = selectEl.getAttribute('data-attribute_name') || '';
            if (!an || !swatchMeta[an] || !swatchMeta[an][slug]) {
                return null;
            }
            var m = swatchMeta[an][slug];
            var hasImg = m.image && String(m.image).length > 0;
            var hasCol = m.color && String(m.color).length > 0;
            if (!hasImg && !hasCol) {
                return null;
            }
            return { color: hasCol ? m.color : '', image: hasImg ? m.image : '' };
        }

        rows.forEach(function (row) {
            var select = row.querySelector('select');
            if (!select) {
                return;
            }
            var td = select.closest('td');
            if (!td) {
                return;
            }
            row.classList.add('wcb-swatch-row');
            var card = document.createElement('div');
            card.className = 'wcb-variation-card';
            var header = document.createElement('div');
            header.className = 'wcb-variation-card__header';
            var labelWrap = document.createElement('div');
            labelWrap.className = 'wcb-variation-card__label';
            var hintSpan = document.createElement('span');
            hintSpan.className = 'wcb-variation-card__hint';
            hintSpan.id = 'wcb-hint-' + select.id;
            hintSpan.textContent = 'Selecione';
            labelWrap.appendChild(hintSpan);
            header.appendChild(labelWrap);
            card.appendChild(header);
            var wrap = document.createElement('div');
            wrap.className = 'wcb-swatch-wrap';
            var options = select.querySelectorAll('option');
            options.forEach(function (opt) {
                if (!opt.value || opt.value === '') {
                    return;
                }
                var labelName = opt.textContent.trim();
                var slug = opt.value;
                var vMeta = getSwatchVisualMeta(select, slug);
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'wcb-swatch-btn';
                btn.setAttribute('data-value', slug);
                if (vMeta) {
                    btn.classList.add('wcb-swatch-btn--dot');
                    btn.setAttribute('aria-label', labelName);
                    btn.title = labelName;
                    var dot = document.createElement('span');
                    dot.className = 'wcb-swatch-dot';
                    dot.setAttribute('aria-hidden', 'true');
                    if (vMeta.image) {
                        dot.style.backgroundColor = 'transparent';
                        dot.style.backgroundImage = 'url("' + wcbSwatchEscapeUrl(vMeta.image) + '")';
                        dot.style.backgroundSize = 'cover';
                        dot.style.backgroundPosition = 'center';
                    } else if (vMeta.color) {
                        dot.style.backgroundColor = vMeta.color;
                    }
                    btn.appendChild(dot);
                } else {
                    btn.textContent = labelName;
                    btn.title = labelName;
                }
                btn.addEventListener('click', function () {
                    if (btn.classList.contains('is-disabled')) {
                        return;
                    }
                    if (btn.classList.contains('is-active')) {
                        btn.classList.remove('is-active');
                        $(select).val('').trigger('change');
                        var hint = document.getElementById('wcb-hint-' + select.id);
                        if (hint) {
                            hint.textContent = 'Selecione';
                            hint.classList.remove('is-selected');
                        }
                        return;
                    }
                    $(select).val(opt.value).trigger('change');
                    wrap.querySelectorAll('.wcb-swatch-btn').forEach(function (b) {
                        b.classList.remove('is-active');
                    });
                    btn.classList.add('is-active');
                    var hint2 = document.getElementById('wcb-hint-' + select.id);
                    if (hint2) {
                        hint2.innerHTML = 'Selecionado: <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="width:14px;height:14px;vertical-align:-2px;margin-right:2px;"><polyline points="20 6 9 17 4 12"/></svg>' + labelName;
                        hint2.classList.add('is-selected');
                    }
                });
                wrap.appendChild(btn);
            });
            if (wrap.querySelector('.wcb-swatch-btn--dot')) {
                wrap.classList.add('wcb-swatch-wrap--dots');
            }
            card.appendChild(wrap);
            td.appendChild(card);
            if (clickInitialIfSelected && select.value) {
                var activeBtn = wrap.querySelector('[data-value="' + select.value + '"]');
                if (activeBtn) {
                    activeBtn.click();
                }
            }
        });

        function applyInitialStockDisable() {
            if (!form) {
                return;
            }
            var variationsJson = form.getAttribute('data-product_variations');
            if (!variationsJson || variationsJson === 'false') {
                return;
            }
            try {
                var allVariations = JSON.parse(variationsJson);
                var inStockValues = {};
                var outOfStockValues = {};
                allVariations.forEach(function (v) {
                    var attrs = v.attributes || {};
                    Object.keys(attrs).forEach(function (key) {
                        var val = attrs[key];
                        if (!val) {
                            return;
                        }
                        if (v.is_in_stock) {
                            if (!inStockValues[key]) {
                                inStockValues[key] = {};
                            }
                            inStockValues[key][val] = true;
                        } else {
                            if (!outOfStockValues[key]) {
                                outOfStockValues[key] = {};
                            }
                            outOfStockValues[key][val] = true;
                        }
                    });
                });
                rows.forEach(function (row) {
                    var sel = row.querySelector('select');
                    var w = row.querySelector('.wcb-swatch-wrap');
                    if (!sel || !w) {
                        return;
                    }
                    var attrName = sel.getAttribute('data-attribute_name') || sel.name;
                    w.querySelectorAll('.wcb-swatch-btn').forEach(function (btn) {
                        var val = btn.getAttribute('data-value');
                        var hasInStock = inStockValues[attrName] && inStockValues[attrName][val];
                        var hasOutOfStock = outOfStockValues[attrName] && outOfStockValues[attrName][val];
                        if (!hasInStock && hasOutOfStock) {
                            btn.classList.add('is-disabled');
                        }
                    });
                });
            } catch (e) { /* ignore */ }
        }

        applyInitialStockDisable();
        setTimeout(applyInitialStockDisable, 100);
        setTimeout(applyInitialStockDisable, 500);

        function syncSwatchStates() {
            rows.forEach(function (row) {
                var select = row.querySelector('select');
                var wrap = row.querySelector('.wcb-swatch-wrap');
                if (!select || !wrap) {
                    return;
                }
                var currentVal = select.value;
                var btns = wrap.querySelectorAll('.wcb-swatch-btn');
                btns.forEach(function (btn) {
                    var val = btn.getAttribute('data-value');
                    var opt = select.querySelector('option[value="' + val + '"]');
                    if (!opt || opt.disabled) {
                        btn.classList.add('is-disabled');
                    } else {
                        btn.classList.remove('is-disabled');
                    }
                });
                btns.forEach(function (btn) {
                    var val = btn.getAttribute('data-value');
                    if (currentVal && val === currentVal) {
                        btn.classList.add('is-active');
                        btn.classList.remove('is-disabled');
                    } else if (!currentVal) {
                        btn.classList.remove('is-active');
                    } else if (val !== currentVal && btn.classList.contains('is-active')) {
                        btn.classList.remove('is-active');
                    }
                });
                var hint = document.getElementById('wcb-hint-' + select.id);
                if (hint) {
                    if (currentVal) {
                        var activeOpt = select.querySelector('option[value="' + currentVal + '"]');
                        var selectedName = activeOpt ? activeOpt.textContent.trim() : currentVal;
                        hint.innerHTML = 'Selecionado: <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="width:14px;height:14px;vertical-align:-2px;margin-right:2px;"><polyline points="20 6 9 17 4 12"/></svg>' + selectedName;
                        hint.classList.add('is-selected');
                    } else {
                        hint.textContent = 'Selecione';
                        hint.classList.remove('is-selected');
                    }
                }
            });
            applyInitialStockDisable();
        }

        var $form = $(form);
        var swNs = ns + '_sw';
        $form.on('woocommerce_update_variation_values.' + swNs, function () {
            setTimeout(syncSwatchStates, 10);
        });
        rows.forEach(function (row) {
            var select = row.querySelector('select');
            if (select) {
                $(select).on('change.' + swNs, function () {
                    setTimeout(syncSwatchStates, 10);
                });
            }
        });
        $form.on('wc_variation_form.' + swNs, function () {
            setTimeout(applyInitialStockDisable, 50);
        });
        $form.on('reset_data.' + swNs, function () {
            rows.forEach(function (row) {
                var wrap = row.querySelector('.wcb-swatch-wrap');
                if (wrap) {
                    wrap.querySelectorAll('.wcb-swatch-btn').forEach(function (b) {
                        b.classList.remove('is-active', 'is-disabled');
                    });
                }
                var select = row.querySelector('select');
                if (select) {
                    var hint = document.getElementById('wcb-hint-' + select.id);
                    if (hint) {
                        hint.textContent = 'Selecione';
                        hint.classList.remove('is-selected');
                    }
                }
            });
        });
    }

    function bindVariationPrices($form, form, ids, ns, subtotalOpts) {
        subtotalOpts = subtotalOpts || false;
        var subIds = subtotalOpts && subtotalOpts.subIds ? subtotalOpts.subIds : null;
        var priceKey = subtotalOpts && subtotalOpts.priceKey ? subtotalOpts.priceKey : 'wcbCurrentVariationPrice';
        var subOn = !!(subIds && subIds.root);
        var prNs = ns + '_pr';
        $form.off('found_variation.' + prNs).on('found_variation.' + prNs, function (e, variation) {
            applyVariationToPriceIds(ids, variation);
            if (subOn) {
                window[priceKey] = parseFloat(variation.display_price) || 0;
                updateSubtotal(form, subIds, priceKey);
            }
        });
        $form.off('reset_data.' + prNs).on('reset_data.' + prNs, function () {
            resetPriceIdsToBase(ids);
            if (subOn) {
                window[priceKey] = 0;
                var subtotalEl = document.getElementById(subIds.root);
                if (subtotalEl) {
                    subtotalEl.style.display = 'none';
                }
            }
        });
    }

    function bindSubtotalQtyListeners(form, subIds, priceKey) {
        if (!form) {
            return;
        }
        subIds = subIds || pdpSubtotalIds();
        priceKey = priceKey || 'wcbCurrentVariationPrice';
        var qtyInput = form.querySelector('input.qty');
        if (qtyInput) {
            qtyInput.addEventListener('change', function () {
                updateSubtotal(form, subIds, priceKey);
            });
            qtyInput.addEventListener('input', function () {
                updateSubtotal(form, subIds, priceKey);
            });
            var qtyObserver = new MutationObserver(function () {
                updateSubtotal(form, subIds, priceKey);
            });
            qtyObserver.observe(qtyInput, { attributes: true, attributeFilter: ['value'] });
        }
    }

    function setupPdpProductBuybox() {
        initBuyboxQtySteppers(document);
        var form = document.querySelector('.variations_form');
        if (!form) {
            return;
        }
        var root = document.getElementById('wcb-pdp-buybox') || document;
        var ids = pdpPriceIds();
        initSwatchCards(root, form, '#wcb-variation-swatch-meta', 'wcbvb_pdp', true);
        bindVariationPrices($(form), form, ids, 'wcbvb_pdp', { subIds: pdpSubtotalIds(), priceKey: 'wcbCurrentVariationPrice' });
        bindSubtotalQtyListeners(form, pdpSubtotalIds(), 'wcbCurrentVariationPrice');
    }

    function getWcAjaxAddToCartUrl() {
        if (typeof wc_add_to_cart_params !== 'undefined' && wc_add_to_cart_params.wc_ajax_url) {
            return wc_add_to_cart_params.wc_ajax_url.toString().replace('%%endpoint%%', 'add_to_cart');
        }
        if (typeof wc_add_to_cart_variation_params !== 'undefined' && wc_add_to_cart_variation_params.wc_ajax_url) {
            return wc_add_to_cart_variation_params.wc_ajax_url.toString().replace('%%endpoint%%', 'add_to_cart');
        }
        return '';
    }

    function setupQuickViewVariableBuybox(opts) {
        opts = opts || {};
        var root = opts.root;
        var form = opts.form;
        var defaultImageUrl = opts.defaultImageUrl || '';
        var onVariationImage = typeof opts.onVariationImage === 'function' ? opts.onVariationImage : null;
        var onResetImage = typeof opts.onResetImage === 'function' ? opts.onResetImage : null;
        if (!root || !form || !$) {
            return;
        }
        var $form = $(form);
        var ids = qvPriceIds();
        initSwatchCards(root, form, '#wcb-variation-swatch-meta-qv', 'wcbvb_qv', false);
        bindVariationPrices($form, form, ids, 'wcbvb_qv', { subIds: qvSubtotalIds(), priceKey: 'wcbQvCurrentVariationPrice' });
        bindSubtotalQtyListeners(form, qvSubtotalIds(), 'wcbQvCurrentVariationPrice');

        if (onVariationImage) {
            $form.off('found_variation.wcbvb_qv_img').on('found_variation.wcbvb_qv_img', function (e, variation) {
                var url = (variation && variation.image && variation.image.src) ? variation.image.src : '';
                if (url) {
                    onVariationImage(url);
                }
            });
        }
        if (onResetImage) {
            $form.off('reset_image.wcbvb_qv_img').on('reset_image.wcbvb_qv_img', function () {
                if (defaultImageUrl) {
                    onResetImage(defaultImageUrl);
                }
            });
        }

        $form.off('submit.wcbvb_qv').on('submit.wcbvb_qv', function (e) {
            e.preventDefault();
            var vidIn = form.querySelector('[name="variation_id"]');
            var vid = vidIn ? (parseInt(vidIn.value, 10) || 0) : 0;
            if (!vid) {
                return;
            }
            var qtyIn = form.querySelector('[name="quantity"]');
            var qty = qtyIn ? (parseInt(qtyIn.value, 10) || 1) : 1;
            var url = getWcAjaxAddToCartUrl();
            if (!url) {
                form.submit();
                return;
            }
            var fd = new FormData();
            fd.append('product_id', String(vid));
            fd.append('quantity', String(qty));
            var btn = form.querySelector('.single_add_to_cart_button');
            if (btn) {
                btn.disabled = true;
                btn.classList.add('loading');
            }
            fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function (r) {
                    return r.json();
                })
                .then(function (data) {
                    if (data && data.error && data.product_url) {
                        window.location = data.product_url;
                        return;
                    }
                    $(document.body).trigger('added_to_cart', [data.fragments, data.cart_hash, $(btn)]);
                    $(document.body).trigger('wc_fragment_refresh');
                })
                .catch(function () { /* ignore */ })
                .finally(function () {
                    if (btn) {
                        btn.disabled = false;
                        btn.classList.remove('loading');
                    }
                });
        });

        initBuyboxQtySteppers(root);
        $form.wc_variation_form();
        setTimeout(function () {
            $form.trigger('woocommerce_update_variation_values');
        }, 120);
    }

    window.WcbVariationBuybox = {
        formatMoneyBr: formatMoneyBr,
        getWcAjaxAddToCartUrl: getWcAjaxAddToCartUrl,
        initBuyboxQtySteppers: initBuyboxQtySteppers,
        setupPdpProductBuybox: setupPdpProductBuybox,
        setupQuickViewVariableBuybox: setupQuickViewVariableBuybox,
        _pdpPriceIds: pdpPriceIds,
        _qvPriceIds: qvPriceIds
    };
}(window, window.jQuery));
