/**
 * Carrinho WooCommerce Blocks — brinde/frete acima do layout; CEP e cupom na sidebar.
 */
(function () {
	var cfg = typeof window.wcbCartPageExtras === 'undefined' ? null : window.wcbCartPageExtras;
	if (!cfg) return;

	var state = {
		appliedCoupons: (cfg.appliedCoupons || []).slice(),
		couponDiscountByCode: Object.assign({}, cfg.couponDiscountByCode || {}),
		chosenShipRateId: cfg.chosenShipRateId ? String(cfg.chosenShipRateId) : '',
		lastCepPostcode: '',
		lastFetchedRates: null,
		wcbShipApplying: false,
	};
	if (cfg.initialProgress) {
		if (cfg.initialProgress.applied_coupons) {
			state.appliedCoupons = cfg.initialProgress.applied_coupons.slice();
		}
		if (cfg.initialProgress.coupon_discount_by_code) {
			state.couponDiscountByCode = Object.assign({}, cfg.initialProgress.coupon_discount_by_code);
		}
	}
	var L = cfg.i18n || {};
	var debounceTimer = null;
	var mergeLineTotalsTimer = null;
	var orderSummaryTimer = null;

	/** No máximo uma execução por frame — reduz trabalho quando Blocks dispara muitas mutações. */
	function wcbRafSchedule(fn) {
		var scheduled = false;
		return function () {
			if (scheduled) {
				return;
			}
			scheduled = true;
			requestAnimationFrame(function () {
				scheduled = false;
				fn();
			});
		};
	}

	/**
	 * Junta o subtotal da linha ao bloco do preço unitário (mesma faixa visual).
	 * O Woo Blocks renderiza <td class="wc-block-cart-item__total"> à parte; movemos só o wrapper interno.
	 */
	function wcbGetCartLineRows() {
		var tbody = document.querySelector('.wc-block-cart-items tbody');
		if (!tbody) {
			return [];
		}
		var all = tbody.querySelectorAll('tr');
		var out = [];
		for (var i = 0; i < all.length; i++) {
			if (all[i].querySelector('td.wc-block-cart-item__product')) {
				out.push(all[i]);
			}
		}
		return out;
	}

	function wcbMergeCartLineTotals() {
		var rows = wcbGetCartLineRows();
		if (!rows.length) {
			return;
		}
		for (var i = 0; i < rows.length; i++) {
			var row = rows[i];
			var product = row.querySelector('td.wc-block-cart-item__product');
			var totalTd = row.querySelector('td.wc-block-cart-item__total');
			if (!product || !totalTd) {
				continue;
			}
			var wrap = product.querySelector('.wc-block-cart-item__wrap');
			var anchor = wrap
				? wrap.querySelector('.wc-block-cart-item__prices') || wrap.querySelector('.wc-block-cart-item__price')
				: product.querySelector('.wc-block-cart-item__prices') || product.querySelector('.wc-block-cart-item__price');
			if (!anchor || !anchor.parentNode) {
				continue;
			}
			var fresh = totalTd.querySelector('.wc-block-cart-item__total-price-and-sale-badge-wrapper');
			var slot = product.querySelector('.wcb-line-total-slot');
			if (!fresh) {
				if (slot && !slot.firstChild) {
					slot.parentNode.removeChild(slot);
				}
				row.classList.remove('wcb-line-total-merged');
				continue;
			}
			if (!slot) {
				slot = document.createElement('div');
				slot.className = 'wcb-line-total-slot';
				anchor.parentNode.insertBefore(slot, anchor.nextSibling);
			}
			if (fresh.parentNode !== slot) {
				slot.appendChild(fresh);
			}
			row.classList.add('wcb-line-total-merged');
		}
	}

	function scheduleMergeLineTotals() {
		clearTimeout(mergeLineTotalsTimer);
		mergeLineTotalsTimer = setTimeout(wcbMergeCartLineTotals, 30);
	}

	function observeCartLineTotalsMergeOnce() {
		var tbody = document.querySelector('.wc-block-cart-items tbody');
		if (!tbody || tbody.dataset.wcbLineTotalMergeObs) {
			return;
		}
		tbody.dataset.wcbLineTotalMergeObs = '1';
		var flushMerge = wcbRafSchedule(scheduleMergeLineTotals);
		var mo = new MutationObserver(function () {
			flushMerge();
		});
		mo.observe(tbody, { childList: true, subtree: true });
	}

	function syncCheckoutCtaLabel() {
		var lbl = cfg.checkoutButtonLabel;
		if (!lbl) {
			return;
		}
		var btn = document.querySelector('a.wc-block-cart__submit-button');
		if (!btn) {
			return;
		}
		btn.textContent = lbl;
		btn.setAttribute('aria-label', lbl);
	}

	function runCheckoutCtaLabelSyncBurst() {
		syncCheckoutCtaLabel();
		[200, 600, 1200, 2500].forEach(function (ms) {
			setTimeout(syncCheckoutCtaLabel, ms);
		});
	}

	function escAttr(s) {
		return String(s == null ? '' : s)
			.replace(/&/g, '&amp;')
			.replace(/"/g, '&quot;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;');
	}
	function escHtml(s) {
		return String(s == null ? '' : s)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;');
	}

	function pgSanKey(k) {
		return String(k == null ? '' : k).replace(/[^a-z0-9_-]/gi, '');
	}

	/** Resumo na sidebar — mesmas linhas que o carrinho lateral (PHP: wcb_xoo_wsc_cart_totals_discounts). */
	function buildPgOrderSummaryMirror(rows) {
		var aria = L.orderSummaryAria ? escAttr(L.orderSummaryAria) : 'Resumo';
		var html =
			'<div class="wcb-pg-order-summary-mirror xoo-wsc-ft-totals wcb-cart-summary" role="region" aria-live="polite" aria-relevant="text" aria-label="' +
			aria +
			'">';
		for (var i = 0; i < rows.length; i++) {
			var r = rows[i];
			var key = pgSanKey(r.key);
			var cls = 'xoo-wsc-ft-amt xoo-wsc-ft-amt-' + escAttr(key);
			if (r.action) cls += ' xoo-wsc-' + escAttr(pgSanKey(r.action));
			html += '<div class="' + cls + '">';
			html += '<span class="xoo-wsc-ft-amt-label">' + escHtml(r.label) + '</span>';
			html += '<span class="xoo-wsc-ft-amt-value">' + (r.value || '') + '</span></div>';
		}
		html += '</div>';
		return html;
	}

	function mountPgOrderSummaryMirrorWithRows(rows) {
		var block = document.querySelector('.wp-block-woocommerce-cart-order-summary-block');
		if (!block || !cfg.orderSummaryAction) return;
		if (!rows || !rows.length) {
			block.classList.remove('wcb-cart-page-order-summary--mirror-active');
			var ex = block.querySelector('.wcb-pg-order-summary-mirror');
			if (ex) ex.remove();
			return;
		}
		cfg.orderSummaryRows = rows;
		var title = block.querySelector('.wc-block-cart__totals-title');
		var existing = block.querySelector('.wcb-pg-order-summary-mirror');
		var html = buildPgOrderSummaryMirror(rows);
		block.classList.add('wcb-cart-page-order-summary--mirror-active');
		if (existing) {
			existing.outerHTML = html;
		} else if (title && title.parentNode === block) {
			title.insertAdjacentHTML('afterend', html);
		} else {
			block.insertAdjacentHTML('afterbegin', html);
		}
	}

	function mountPgOrderSummaryMirror() {
		mountPgOrderSummaryMirrorWithRows(cfg.orderSummaryRows || []);
	}

	function fetchPgOrderSummaryRows(cb) {
		if (!cfg.orderSummaryAction || !cfg.nonceSideCart) {
			if (typeof cb === 'function') cb(null);
			return;
		}
		var body = new URLSearchParams();
		body.set('action', cfg.orderSummaryAction);
		body.set('nonce', cfg.nonceSideCart);
		fetch(cfg.ajaxUrl, {
			method: 'POST',
			body: body,
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
		})
			.then(function (r) {
				return r.json();
			})
			.then(function (data) {
				if (!data || !data.success || !data.data || !Array.isArray(data.data.rows)) {
					if (typeof cb === 'function') cb(null);
					return;
				}
				if (typeof cb === 'function') cb(data.data.rows);
			})
			.catch(function () {
				if (typeof cb === 'function') cb(null);
			});
	}

	function scheduleOrderSummaryRefresh() {
		clearTimeout(orderSummaryTimer);
		orderSummaryTimer = setTimeout(function () {
			fetchPgOrderSummaryRows(function (rows) {
				if (rows === null) {
					return;
				}
				mountPgOrderSummaryMirrorWithRows(rows);
			});
		}, 150);
	}

	function subscribeWpDataCartForSummary() {
		if (typeof wp === 'undefined' || !wp.data || typeof wp.data.subscribe !== 'function') {
			return;
		}
		var sel;
		try {
			sel = wp.data.select('wc/store/cart');
			if (!sel || typeof sel.getCartData !== 'function') {
				return;
			}
		} catch (e) {
			return;
		}
		var prev = '';
		wp.data.subscribe(function () {
			try {
				var cart = sel.getCartData();
				var sig = '';
				if (cart && cart.cartTotals && typeof cart.cartTotals === 'object') {
					sig = JSON.stringify(cart.cartTotals);
				} else if (cart && cart.totals) {
					sig = JSON.stringify(cart.totals);
				}
				if (sig === prev) {
					return;
				}
				prev = sig;
				scheduleOrderSummaryRefresh();
			} catch (err) { /* */ }
		});
	}

	function invalidateBlocksCart() {
		try {
			document.body.dispatchEvent(
				new CustomEvent('wc-blocks_added_to_cart', { bubbles: true, detail: {} })
			);
		} catch (e) { /* */ }
	}

	function wcbNormRateId(id) {
		if (id === null || id === undefined || id === '') return '';
		return String(id);
	}

	function wcbShipRecommendedIndex(rates) {
		var i;
		for (i = 0; i < rates.length; i++) {
			if (rates[i].free) return i;
		}
		return 0;
	}

	function fetchProgress(cb) {
		var fd = new FormData();
		fd.append('action', 'wcb_gift_progress_data');
		fetch(cfg.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
			.then(function (r) {
				return r.json();
			})
			.then(function (d) {
				if (d.applied_coupons) state.appliedCoupons = d.applied_coupons;
				if (d.coupon_discount_by_code) state.couponDiscountByCode = d.coupon_discount_by_code;
				if (typeof cb === 'function') cb(d);
			})
			.catch(function () {
				if (typeof cb === 'function') cb(null);
			});
	}

	function mapBarData(d) {
		if (!d) return null;
		return {
			subtotal: d.subtotal,
			gift_progress: d.progress,
			gift_unlocked: !!d.unlocked,
			gift_text: d.gift_text || '',
			ship_progress: d.ship_progress,
			ship_remaining: d.ship_remaining,
			ship_unlocked: !!d.ship_unlocked,
		};
	}

	function buildGiftBar(data) {
		var cls = 'wcb-gift-progress' + (data.gift_unlocked ? ' wcb-gift-unlocked' : '');
		var kickerGift = L.incentiveKickerGift ? escHtml(L.incentiveKickerGift) : '';
		return (
			'<div class="' +
			cls +
			'">' +
			(kickerGift ? '<span class="wcb-incentive-suite__kicker">' + kickerGift + '</span>' : '') +
			'<div class="wcb-gift-progress-text">' +
			'<span class="wcb-gift-progress__icon" aria-hidden="true">' +
			cfg.svgGift +
			'</span>' +
			'<span class="wcb-gift-progress__copy">' +
			data.gift_text +
			'</span></div>' +
			'<div class="wcb-gift-progress-bar"><div class="wcb-gift-progress-fill" style="width:' +
			Math.min(100, data.gift_progress) +
			'%"></div></div></div>'
		);
	}

	function buildShipBar(data) {
		var cls = 'wcb-ship-bar' + (data.ship_unlocked ? ' wcb-ship-bar--unlocked' : '');
		var icon = data.ship_unlocked ? cfg.svgShipOk : cfg.svgTruck;
		var text;
		if (data.ship_unlocked) {
			text =
				'<strong class="wcb-incentive-accent">' +
				escHtml(L.shipUnlocked) +
				'</strong> ' +
				escHtml(L.shipUnlockedTail);
		} else if (data.subtotal <= 0) {
			text =
				escHtml(L.shipBuyPrefix) +
				' <strong class="wcb-incentive-accent">R$ ' +
				Number(cfg.shipThreshold).toLocaleString('pt-BR', { minimumFractionDigits: 2 }) +
				'</strong> ' +
				escHtml(L.shipFor) +
				' <strong class="wcb-incentive-accent">' +
				escHtml(L.shipFreeLabel) +
				'</strong>';
		} else {
			text =
				escHtml(L.shipMissing) +
				' <strong class="wcb-incentive-accent">R$ ' +
				Number(data.ship_remaining).toLocaleString('pt-BR', { minimumFractionDigits: 2 }) +
				'</strong> ' +
				escHtml(L.shipFor) +
				' <strong class="wcb-incentive-accent">' +
				escHtml(L.shipFreeLabel) +
				'</strong>';
		}
		var kickerShip = L.incentiveKickerShip ? escHtml(L.incentiveKickerShip) : '';
		return (
			'<div class="' +
			cls +
			'">' +
			(kickerShip ? '<span class="wcb-incentive-suite__kicker">' + kickerShip + '</span>' : '') +
			'<div class="wcb-ship-bar__text">' +
			'<span class="wcb-ship-bar__icon" aria-hidden="true">' +
			icon +
			'</span>' +
			'<span class="wcb-ship-bar__copy">' +
			text +
			'</span></div>' +
			'<div class="wcb-ship-bar__track"><div class="wcb-ship-bar__fill" style="width:' +
			Math.min(100, data.ship_progress) +
			'%"></div></div></div>'
		);
	}

	function buildIncentiveStack(data) {
		return (
			'<div id="wcb-pg-incentive-stack" class="wcb-incentive-rail wcb-incentive-rail--cart-page-stack" role="region" aria-label="' +
			escAttr(L.ariaIncentives) +
			'">' +
			'<div class="wcb-incentive-rail__stack-inner wcb-incentive-suite">' +
			buildGiftBar(data) +
			buildShipBar(data) +
			'</div></div>'
		);
	}

	function updateIncentiveDom(data) {
		var inner = document.querySelector(
			'#wcb-pg-incentive-stack .wcb-incentive-rail__stack-inner'
		);
		if (!inner || !data) return;
		inner.innerHTML = buildGiftBar(data) + buildShipBar(data);
	}

	function buildCouponBlock() {
		return (
			'<div class="wcb-coupon-block" id="wcb-pg-coupon-block">' +
			'<div class="wcb-stack-card wcb-stack-card--coupon">' +
			'<label class="wcb-stack-card__label" for="wcb-pg-coupon-input">' +
			escHtml(L.couponLabel) +
			'</label>' +
			'<div class="wcb-stack-card__body">' +
			'<div class="wcb-checkout-stage wcb-coupon-stage" data-wcb-stage="coupon">' +
			'<div class="wcb-coupon-layer wcb-coupon-layer--idle" id="wcb-pg-coupon-layer-idle" aria-hidden="false">' +
			'<div class="wcb-coupon-block__row">' +
			'<input type="text" class="wcb-coupon-input" id="wcb-pg-coupon-input" placeholder="' +
			escAttr(L.couponPlaceholder) +
			'" autocomplete="off">' +
			'<button type="button" class="wcb-coupon-apply" id="wcb-pg-coupon-apply">' +
			escHtml(L.couponApply) +
			'</button></div></div>' +
			'<div class="wcb-coupon-layer wcb-coupon-layer--applied" id="wcb-pg-coupon-layer-applied" aria-hidden="true">' +
			'<div class="wcb-coupon-applied-inner" id="wcb-pg-coupon-applied-inner"></div></div></div></div>' +
			'<div class="wcb-coupon-msg" id="wcb-pg-coupon-msg" role="status"></div></div></div>'
		);
	}

	function buildCepBlock() {
		var v = cfg.userPostcode ? ' value="' + escAttr(cfg.userPostcode) + '"' : '';
		return (
			'<div class="wcb-cep-block" id="wcb-pg-cep-block">' +
			'<div class="wcb-stack-card wcb-stack-card--cep">' +
			'<label class="wcb-stack-card__label" for="wcb-pg-cep-input">' +
			escHtml(L.cepLabel) +
			'</label>' +
			'<div class="wcb-stack-card__body">' +
			'<div class="wcb-checkout-stage wcb-cep-stage" data-wcb-stage="cep">' +
			'<div class="wcb-cep-layer wcb-cep-layer--idle" id="wcb-pg-cep-layer-idle" aria-hidden="false">' +
			'<div class="wcb-cep-block__row">' +
			'<input type="text" class="wcb-cep-input" id="wcb-pg-cep-input" placeholder="00000-000" maxlength="9" inputmode="numeric"' +
			v +
			'>' +
			'<button type="button" class="wcb-cep-btn" id="wcb-pg-cep-btn">' +
			escHtml(L.cepCalc) +
			'</button></div>' +
			'<div class="wcb-cep-layer__skeleton" aria-hidden="true"></div></div>' +
			'<div class="wcb-cep-layer wcb-cep-layer--done" id="wcb-pg-cep-layer-done" aria-hidden="true">' +
			'<div class="wcb-cep-applied-inner" id="wcb-pg-cep-applied-inner"></div></div></div></div>' +
			'<div class="wcb-cep-inline-msg" id="wcb-pg-cep-inline-msg"></div></div></div>'
		);
	}

	function syncCouponUi() {
		var block = document.getElementById('wcb-pg-coupon-block');
		var idle = document.getElementById('wcb-pg-coupon-layer-idle');
		var applied = document.getElementById('wcb-pg-coupon-layer-applied');
		var inner = document.getElementById('wcb-pg-coupon-applied-inner');
		if (!block || !idle || !applied || !inner) return;
		var codes = state.appliedCoupons || [];
		if (!codes.length) {
			inner.innerHTML = '';
			block.classList.remove('wcb-coupon-block--applied');
			idle.setAttribute('aria-hidden', 'false');
			applied.setAttribute('aria-hidden', 'true');
			return;
		}
		var discMap = state.couponDiscountByCode || {};
		var parts = codes.map(function (c) {
			var disc = discMap[c] || '';
			var textHtml = '<strong>' + escHtml(c) + '</strong>';
			if (disc) {
				textHtml +=
					' <span class="wcb-coupon-summary__sep" aria-hidden="true">·</span> <span class="wcb-coupon-summary__amount">' +
					escHtml(disc) +
					'</span>';
			}
			return (
				'<div class="wcb-coupon-summary__row">' +
				'<span class="wcb-coupon-summary__ok" aria-hidden="true">✓</span>' +
				'<span class="wcb-coupon-summary__text">' +
				textHtml +
				'</span>' +
				'<button type="button" class="wcb-coupon-summary__rm wcb-coupon-status__rm" data-code="' +
				escAttr(c) +
				'" aria-label="' +
				escAttr(L.couponRmAria) +
				'">' +
				escHtml(L.couponRm) +
				'</button></div>'
			);
		});
		inner.innerHTML = parts.join('');
		block.classList.add('wcb-coupon-block--applied');
		idle.setAttribute('aria-hidden', 'true');
		applied.setAttribute('aria-hidden', 'false');
	}

	function setCouponMsg(text, isErr) {
		var msgEl = document.getElementById('wcb-pg-coupon-msg');
		if (!msgEl) return;
		msgEl.textContent = text || '';
		msgEl.className = 'wcb-coupon-msg' + (isErr ? ' wcb-coupon-msg--error' : '');
	}

	function setCepInlineMsg(html, isErr) {
		var el = document.getElementById('wcb-pg-cep-inline-msg');
		if (!el) return;
		el.innerHTML = html || '';
		el.className = 'wcb-cep-inline-msg' + (isErr ? ' wcb-cep-inline-msg--error' : '');
	}

	function ensurePgModal() {
		var m = document.getElementById('wcb-pg-ship-modal');
		if (m) return m;
		document.body.insertAdjacentHTML(
			'beforeend',
			'<div id="wcb-pg-ship-modal" class="wcb-ship-modal" hidden aria-hidden="true">' +
				'<div class="wcb-ship-modal__backdrop" tabindex="-1"></div>' +
				'<div class="wcb-ship-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="wcb-pg-ship-modal-title">' +
				'<div class="wcb-ship-modal__head">' +
				'<div class="wcb-ship-modal__head-text">' +
				'<h2 id="wcb-pg-ship-modal-title" class="wcb-ship-modal__title">' +
				escHtml(L.shipModalTitle) +
				'</h2>' +
				'<p class="wcb-ship-modal__subtitle">' +
				escHtml(L.shipModalSub) +
				'</p></div>' +
				'<button type="button" class="wcb-ship-modal__close" aria-label="' +
				escAttr(L.shipModalClose) +
				'">&times;</button></div>' +
				'<div class="wcb-ship-modal__subhead">' +
				'<div class="wcb-ship-modal__cep-row">' +
				'<span class="wcb-ship-modal__cep-badge" id="wcb-pg-ship-modal-cep-disp"></span>' +
				'<button type="button" class="wcb-ship-modal__cep-edit" id="wcb-pg-ship-modal-edit-cep">' +
				escHtml(L.cepEdit) +
				'</button></div></div>' +
				'<div class="wcb-ship-modal__body" id="wcb-pg-ship-modal-rates"></div>' +
				'<div class="wcb-ship-modal__footer">' +
				'<button type="button" class="wcb-ship-modal__continue" id="wcb-pg-ship-modal-continue">' +
				escHtml(L.shipConfirm) +
				'</button></div></div></div>'
		);
		m = document.getElementById('wcb-pg-ship-modal');
		m.querySelector('.wcb-ship-modal__backdrop').addEventListener('click', closePgModal);
		m.querySelector('.wcb-ship-modal__close').addEventListener('click', closePgModal);
		return m;
	}

	function closePgModal() {
		var modal = document.getElementById('wcb-pg-ship-modal');
		if (!modal) return;
		modal.hidden = true;
		modal.setAttribute('aria-hidden', 'true');
		document.body.classList.remove('wcb-ship-modal-open');
		var cepB = document.getElementById('wcb-pg-cep-block');
		if (cepB) cepB.classList.remove('wcb-cep-block--loading');
	}

	function openPgModalLoading(cepFmt) {
		state.wcbShipApplying = false;
		var modal = ensurePgModal();
		var contLbl = document.getElementById('wcb-pg-ship-modal-continue');
		if (contLbl) {
			contLbl.textContent = L.shipWait;
			contLbl.disabled = true;
			contLbl.onclick = null;
		}
		var cepDisp = document.getElementById('wcb-pg-ship-modal-cep-disp');
		if (cepDisp) {
			cepDisp.textContent = cepFmt ? L.cepBadge + ' ' + cepFmt : '';
		}
		var body = document.getElementById('wcb-pg-ship-modal-rates');
		if (body) {
			body.innerHTML =
				'<div class="wcb-ship-modal__loading" role="status" aria-live="polite"><span class="wcb-ship-modal__loading-inner">' +
				escHtml(L.shipLoading) +
				'</span></div>';
		}
		modal.hidden = false;
		modal.setAttribute('aria-hidden', 'false');
		document.body.classList.add('wcb-ship-modal-open');
	}

	function buildShipModalRatesHtml(rates, selectedId) {
		var recIdx = wcbShipRecommendedIndex(rates);
		var hintEta = L.shipEtaHint;
		var html =
			'<ul class="wcb-ship-opt-list" role="radiogroup" aria-label="' + escAttr(L.shipOptsAria) + '">';
		rates.forEach(function (r, i) {
			var isRec = i === recIdx;
			var isSel = wcbNormRateId(r.id) === wcbNormRateId(selectedId);
			var etaRaw = r.eta && String(r.eta).trim() ? String(r.eta).trim() : '';
			var eta = escHtml(etaRaw || hintEta);
			var freeCls = r.free ? ' wcb-ship-opt__card--free' : '';
			var selCls = isSel ? ' is-selected' : '';
			var priceInner = r.free
				? '<span class="wcb-ship-opt__price wcb-ship-opt__price--free"><span class="wcb-ship-opt__price-main">' +
				  escHtml(L.freeShip) +
				  '</span><span class="wcb-ship-opt__price-sub">' +
				  escHtml(L.shipSaveTotal) +
				  '</span></span>'
				: '<span class="wcb-ship-opt__price"><span class="wcb-ship-opt__price-main">' +
				  escHtml(r.cost_f) +
				  '</span></span>';
			var badge = isRec
				? '<span class="wcb-ship-opt__badge">' +
				  escHtml(r.free ? L.shipRecFree : L.shipRecPaid) +
				  '</span>'
				: '';
			html +=
				'<li class="wcb-ship-opt" role="none">' +
				'<button type="button" class="wcb-ship-opt__card' +
				freeCls +
				selCls +
				'" role="radio" aria-checked="' +
				(isSel ? 'true' : 'false') +
				'" data-rate-id="' +
				escAttr(String(r.id)) +
				'">' +
				'<span class="wcb-ship-opt__radio" aria-hidden="true"></span>' +
				'<span class="wcb-ship-opt__mid">' +
				'<span class="wcb-ship-opt__title-row">' +
				'<span class="wcb-ship-opt__name">' +
				escHtml(r.label) +
				'</span>' +
				badge +
				'</span>' +
				'<span class="wcb-ship-opt__eta">' +
				eta +
				'</span></span>' +
				priceInner +
				'</button></li>';
		});
		html += '</ul>';
		return html;
	}

	function saveShipSummary(cepDigits, label, costText, rateId) {
		try {
			window.sessionStorage.setItem(
				cfg.shipSumKey,
				JSON.stringify({
					cep: cepDigits,
					label: label,
					cost: costText,
					rateId: rateId,
				})
			);
		} catch (e) { /* */ }
		state.chosenShipRateId = wcbNormRateId(rateId);
		renderCepSummary(label, costText);
	}

	function renderCepSummary(label, costText) {
		var block = document.getElementById('wcb-pg-cep-block');
		var idle = document.getElementById('wcb-pg-cep-layer-idle');
		var done = document.getElementById('wcb-pg-cep-layer-done');
		var inner = document.getElementById('wcb-pg-cep-applied-inner');
		if (!block || !idle || !done || !inner) return;
		if (!label) {
			inner.innerHTML = '';
			block.classList.remove('wcb-cep-block--done', 'wcb-cep-block--loading');
			idle.setAttribute('aria-hidden', 'false');
			done.setAttribute('aria-hidden', 'true');
			return;
		}
		var cost = costText || '';
		inner.innerHTML =
			'<div class="wcb-cep-summary__row">' +
			'<span class="wcb-cep-summary__ok" aria-hidden="true">✓</span>' +
			'<span class="wcb-cep-summary__text"><strong>' +
			escHtml(label) +
			'</strong> · ' +
			escHtml(cost) +
			'</span>' +
			'<button type="button" class="wcb-cep-summary__change">' +
			escHtml(L.cepChange) +
			'</button></div>';
		block.classList.remove('wcb-cep-block--loading');
		block.classList.add('wcb-cep-block--done');
		idle.setAttribute('aria-hidden', 'true');
		done.setAttribute('aria-hidden', 'false');
	}

	function loadShipSummaryFromStorage() {
		try {
			var raw = window.sessionStorage.getItem(cfg.shipSumKey);
			return raw ? JSON.parse(raw) : null;
		} catch (e) {
			return null;
		}
	}

	function syncShipSummaryFromStorage() {
		var data = loadShipSummaryFromStorage();
		var ci = document.getElementById('wcb-pg-cep-input');
		if (!data || !ci || ci.value.replace(/\D/g, '') !== data.cep) return;
		state.chosenShipRateId = wcbNormRateId(data.rateId);
		renderCepSummary(data.label, data.cost);
	}

	function postSetShipping(rateId, rateMeta) {
		var cont = document.getElementById('wcb-pg-ship-modal-continue');
		var prevLabel = cont ? cont.textContent : '';
		if (state.wcbShipApplying) return;
		state.wcbShipApplying = true;
		if (cont) {
			cont.disabled = true;
			cont.textContent = L.shipApplying;
		}
		var body = new URLSearchParams();
		body.set('action', 'wcb_side_cart_set_shipping');
		body.set('nonce', cfg.nonceSideCart);
		body.set('rate_id', wcbNormRateId(rateId));
		fetch(cfg.ajaxUrl, {
			method: 'POST',
			body: body,
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
		})
			.then(function (r) {
				return r.json();
			})
			.then(function (data) {
				state.wcbShipApplying = false;
				if (data.success === false) {
					if (cont) {
						cont.disabled = false;
						cont.textContent = prevLabel || L.shipConfirm;
					}
					var msg =
						data.data && data.data.message
							? String(data.data.message)
							: L.shipFail;
					setCepInlineMsg('<span class="wcb-cep-error">' + escHtml(msg) + '</span>', true);
					return;
				}
				if (data.applied_coupons) state.appliedCoupons = data.applied_coupons;
				if (data.coupon_discount_by_code) {
					state.couponDiscountByCode = data.coupon_discount_by_code;
				}
				state.chosenShipRateId = wcbNormRateId(rateId);
				if (rateMeta && state.lastCepPostcode) {
					var costText = rateMeta.free ? L.freeShip : rateMeta.cost_f || '';
					saveShipSummary(state.lastCepPostcode, rateMeta.label, costText, state.chosenShipRateId);
				}
				closePgModal();
				if (cont) cont.textContent = prevLabel || L.shipConfirm;
				syncCouponUi();
				invalidateBlocksCart();
				scheduleOrderSummaryRefresh();
				fetchProgress(function (d) {
					var bd = mapBarData(d);
					if (bd) updateIncentiveDom(bd);
				});
			})
			.catch(function () {
				state.wcbShipApplying = false;
				if (cont) {
					cont.disabled = false;
					cont.textContent = prevLabel || L.shipConfirm;
				}
				setCepInlineMsg('<span class="wcb-cep-error">' + escHtml(L.shipNetErr) + '</span>', true);
			});
	}

	function openPgModal(rates, cepFmt) {
		state.wcbShipApplying = false;
		state.lastFetchedRates = rates;
		var modal = ensurePgModal();
		var contLbl = document.getElementById('wcb-pg-ship-modal-continue');
		if (contLbl) contLbl.textContent = L.shipConfirm;
		var cepDisp = document.getElementById('wcb-pg-ship-modal-cep-disp');
		if (cepDisp) cepDisp.textContent = cepFmt ? L.cepBadge + ' ' + cepFmt : '';

		var recIdx = wcbShipRecommendedIndex(rates);
		var cid = wcbNormRateId(state.chosenShipRateId);
		var hasChosen = cid !== '' && rates.some(function (x) {
			return wcbNormRateId(x.id) === cid;
		});
		var selectedId = hasChosen ? state.chosenShipRateId : rates[recIdx].id;

		var bodyEl = document.getElementById('wcb-pg-ship-modal-rates');
		bodyEl.innerHTML = buildShipModalRatesHtml(rates, selectedId);

		function findRate(id) {
			var want = wcbNormRateId(id);
			var out = null;
			rates.forEach(function (x) {
				if (wcbNormRateId(x.id) === want) out = x;
			});
			return out;
		}

		function setModalSelection(rateId) {
			var rid = wcbNormRateId(rateId);
			bodyEl.querySelectorAll('.wcb-ship-opt__card').forEach(function (btn) {
				var on = wcbNormRateId(btn.getAttribute('data-rate-id')) === rid;
				btn.classList.toggle('is-selected', on);
				btn.setAttribute('aria-checked', on ? 'true' : 'false');
			});
			var cont = document.getElementById('wcb-pg-ship-modal-continue');
			if (cont) cont.disabled = rid === '';
		}

		bodyEl.querySelectorAll('.wcb-ship-opt__card').forEach(function (btn) {
			btn.addEventListener('click', function () {
				setModalSelection(this.getAttribute('data-rate-id'));
			});
		});

		setModalSelection(selectedId);

		var cont = document.getElementById('wcb-pg-ship-modal-continue');
		if (cont) {
			cont.onclick = function () {
				if (state.wcbShipApplying) return;
				var sel = bodyEl.querySelector('.wcb-ship-opt__card.is-selected');
				if (!sel) return;
				var id = sel.getAttribute('data-rate-id');
				var rate = findRate(id);
				if (rate) postSetShipping(id, rate);
			};
		}

		var editCep = document.getElementById('wcb-pg-ship-modal-edit-cep');
		if (editCep) {
			editCep.onclick = function () {
				closePgModal();
				renderCepSummary('', '');
				try {
					window.sessionStorage.removeItem(cfg.shipSumKey);
				} catch (e) { /* */ }
				state.lastCepPostcode = '';
				state.lastFetchedRates = null;
				state.chosenShipRateId = '';
				var inp = document.getElementById('wcb-pg-cep-input');
				if (inp) {
					inp.focus();
					try {
						inp.select();
					} catch (ex) { /* */ }
				}
			};
		}

		modal.hidden = false;
		modal.setAttribute('aria-hidden', 'false');
		document.body.classList.add('wcb-ship-modal-open');
		var toFocus =
			bodyEl.querySelector('.wcb-ship-opt__card.is-selected') ||
			bodyEl.querySelector('.wcb-ship-opt__card');
		if (toFocus) toFocus.focus();
	}

	function initCoupon() {
		var block = document.getElementById('wcb-pg-coupon-block');
		if (!block || block.getAttribute('data-wcb-inited') === '1') return;
		block.setAttribute('data-wcb-inited', '1');
		var input = document.getElementById('wcb-pg-coupon-input');
		var btn = document.getElementById('wcb-pg-coupon-apply');
		if (!input || !btn) return;

		function applyCoupon() {
			var code = input.value.replace(/^\s+|\s+$/g, '');
			if (!code) {
				setCouponMsg(L.couponEmpty, true);
				return;
			}
			btn.disabled = true;
			setCouponMsg('');
			var body = new URLSearchParams();
			body.set('action', 'wcb_side_cart_apply_coupon');
			body.set('nonce', cfg.nonceSideCart);
			body.set('coupon_code', code);
			fetch(cfg.ajaxUrl, {
				method: 'POST',
				body: body,
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			})
				.then(function (r) {
					return r.json();
				})
				.then(function (data) {
					btn.disabled = false;
					if (data.success === false) {
						var m =
							data.data && data.data.message
								? String(data.data.message)
								: L.couponFail;
						setCouponMsg(m, true);
						return;
					}
					if (data.applied_coupons) state.appliedCoupons = data.applied_coupons;
					if (data.coupon_discount_by_code) {
						state.couponDiscountByCode = data.coupon_discount_by_code;
					}
					input.value = '';
					setCouponMsg('');
					syncCouponUi();
					invalidateBlocksCart();
					scheduleOrderSummaryRefresh();
					fetchProgress(function (d) {
						var bd = mapBarData(d);
						if (bd) updateIncentiveDom(bd);
					});
				})
				.catch(function () {
					btn.disabled = false;
					setCouponMsg(L.couponNetErr, true);
				});
		}

		btn.addEventListener('click', applyCoupon);
		input.addEventListener('keydown', function (e) {
			if (e.key === 'Enter') applyCoupon();
		});

		block.addEventListener('click', function (e) {
			var rm = e.target.closest('.wcb-coupon-summary__rm, .wcb-coupon-status__rm');
			if (!rm) return;
			e.preventDefault();
			var code = rm.getAttribute('data-code') || '';
			if (!code) return;
			rm.disabled = true;
			var body = new URLSearchParams();
			body.set('action', 'wcb_side_cart_remove_coupon');
			body.set('nonce', cfg.nonceSideCart);
			body.set('coupon', code);
			fetch(cfg.ajaxUrl, {
				method: 'POST',
				body: body,
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			})
				.then(function (r) {
					return r.json();
				})
				.then(function (data) {
					rm.disabled = false;
					if (data.success === false) return;
					if (data.applied_coupons) state.appliedCoupons = data.applied_coupons;
					if (data.coupon_discount_by_code) {
						state.couponDiscountByCode = data.coupon_discount_by_code;
					}
					syncCouponUi();
					invalidateBlocksCart();
					scheduleOrderSummaryRefresh();
					fetchProgress(function (d) {
						var bd = mapBarData(d);
						if (bd) updateIncentiveDom(bd);
					});
				})
				.catch(function () {
					rm.disabled = false;
				});
		});

		syncCouponUi();
	}

	function initCep() {
		var cepBlock = document.getElementById('wcb-pg-cep-block');
		if (cepBlock && cepBlock.getAttribute('data-wcb-inited') === '1') return;
		var input = document.getElementById('wcb-pg-cep-input');
		var btn = document.getElementById('wcb-pg-cep-btn');
		if (!input || !btn) return;
		if (cepBlock) cepBlock.setAttribute('data-wcb-inited', '1');

		input.addEventListener('input', function () {
			var v = this.value.replace(/\D/g, '').slice(0, 8);
			this.value = v.length > 5 ? v.slice(0, 5) + '-' + v.slice(5) : v;
			var st = loadShipSummaryFromStorage();
			if (st && v !== st.cep && v.length > 0) {
				try {
					window.sessionStorage.removeItem(cfg.shipSumKey);
				} catch (e) { /* */ }
				renderCepSummary('', '');
			}
			setCepInlineMsg('', false);
		});

		input.addEventListener('keydown', function (e) {
			if (e.key === 'Enter') btn.click();
		});

		if (cepBlock) {
			cepBlock.addEventListener('click', function (e) {
				if (!e.target.closest('.wcb-cep-summary__change')) return;
				e.preventDefault();
				setCepInlineMsg('', false);
				renderCepSummary('', '');
				try {
					window.sessionStorage.removeItem(cfg.shipSumKey);
				} catch (err) { /* */ }
				state.lastCepPostcode = '';
				state.lastFetchedRates = null;
				state.chosenShipRateId = '';
				if (input) {
					input.focus();
					try {
						input.select();
					} catch (ex) { /* */ }
				}
			});
		}

		btn.addEventListener('click', function () {
			var postcode = input.value.replace(/\D/g, '');
			if (postcode.length < 8) {
				setCepInlineMsg('<span class="wcb-cep-error">' + escHtml(L.cepInvalid) + '</span>', true);
				return;
			}
			if (btn.disabled) return;
			btn.disabled = true;
			var prevTxt = btn.textContent;
			btn.textContent = '…';
			setCepInlineMsg('', false);
			if (cepBlock) cepBlock.classList.add('wcb-cep-block--loading');
			var cepFmt =
				postcode.length === 8 ? postcode.slice(0, 5) + '-' + postcode.slice(5) : postcode;
			openPgModalLoading(cepFmt);

			var fd = new FormData();
			fd.append('action', 'wcb_calc_shipping');
			fd.append('postcode', postcode);
			fd.append('nonce', cfg.nonceCep);

			fetch(cfg.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
				.then(function (r) {
					return r.json();
				})
				.then(function (res) {
					btn.disabled = false;
					btn.textContent = prevTxt;
					if (cepBlock) cepBlock.classList.remove('wcb-cep-block--loading');
					if (!res.success) {
						state.lastCepPostcode = '';
						closePgModal();
						var errRaw = res.data;
						var errStr =
							typeof errRaw === 'string'
								? errRaw
								: errRaw && errRaw.message
									? String(errRaw.message)
									: L.cepNotFound;
						setCepInlineMsg('<span class="wcb-cep-error">' + escHtml(errStr) + '</span>', true);
						return;
					}
					var rates = res.data;
					state.lastCepPostcode = postcode;
					setCepInlineMsg('', false);
					if (rates.length === 1) {
						closePgModal();
						postSetShipping(rates[0].id, rates[0]);
						return;
					}
					openPgModal(rates, cepFmt);
				})
				.catch(function () {
					btn.disabled = false;
					btn.textContent = prevTxt;
					if (cepBlock) cepBlock.classList.remove('wcb-cep-block--loading');
					closePgModal();
					setCepInlineMsg('<span class="wcb-cep-error">' + escHtml(L.cepErrCalc) + '</span>', true);
				});
		});

		syncShipSummaryFromStorage();
	}

	function getBlocksCartLayout() {
		var wrap = document.querySelector('.wp-block-woocommerce-filled-cart-block');
		if (wrap) {
			if (
				wrap.classList.contains('wc-block-components-sidebar-layout') &&
				wrap.classList.contains('wc-block-cart')
			) {
				return wrap;
			}
			var inner = wrap.querySelector('.wc-block-components-sidebar-layout.wc-block-cart');
			if (inner) {
				return inner;
			}
		}
		var el = document.querySelector('.wc-block-cart.wp-block-woocommerce-filled-cart-block');
		if (el) {
			return el;
		}
		return document.querySelector('.wc-block-components-sidebar-layout.wc-block-cart');
	}

	function isPremiumStackMounted() {
		return !!(
			document.getElementById('wcb-cart-page-premium-stack') &&
			document.getElementById('wcb-cart-page-premium-incentives')
		);
	}

	/** Stack antigo: incentivos dentro da sidebar → sobe para #wcb-cart-page-premium-incentives */
	function migrateLegacyIncentivesToTop() {
		if (document.getElementById('wcb-cart-page-premium-incentives')) {
			return;
		}
		var stack = document.getElementById('wcb-cart-page-premium-stack');
		if (!stack) {
			return;
		}
		var rail = stack.querySelector('#wcb-pg-incentive-stack');
		if (!rail) {
			return;
		}
		var layout = getBlocksCartLayout();
		if (!layout || !layout.parentElement) {
			return;
		}
		var wrap = document.createElement('div');
		wrap.id = 'wcb-cart-page-premium-incentives';
		wrap.className = 'wcb-cart-page-premium-incentives';
		wrap.appendChild(rail);
		layout.parentElement.insertBefore(wrap, layout);
	}

	/**
	 * CEP + cupom: logo abaixo do resumo (totais → frete/cupom → checkout).
	 */
	function placePremiumStackInSidebar(side, stackEl) {
		if (!side || !stackEl) {
			return;
		}
		var summary = side.querySelector('.wp-block-woocommerce-cart-order-summary-block');
		if (summary) {
			summary.insertAdjacentElement('afterend', stackEl);
			return;
		}
		var submit =
			side.querySelector('.wc-block-cart__submit-container') ||
			side.querySelector('.wc-block-cart__submit');
		if (submit) {
			submit.insertAdjacentElement('beforebegin', stackEl);
		} else {
			side.appendChild(stackEl);
		}
	}

	function ensurePremiumStackAfterOrderSummary(side) {
		var stack = document.getElementById('wcb-cart-page-premium-stack');
		if (!stack || !side || !side.contains(stack)) {
			return;
		}
		var summary = side.querySelector('.wp-block-woocommerce-cart-order-summary-block');
		if (!summary) {
			return;
		}
		if (stack.previousElementSibling !== summary) {
			summary.insertAdjacentElement('afterend', stack);
		}
	}

	function mount() {
		var layout = getBlocksCartLayout();
		var side = document.querySelector('.wc-block-cart__sidebar');
		if (!layout || !layout.parentElement || !side) {
			return false;
		}
		migrateLegacyIncentivesToTop();
		ensurePremiumStackAfterOrderSummary(side);
		if (isPremiumStackMounted()) {
			return true;
		}
		var anchor = layout.parentElement;
		if (anchor.getAttribute('data-wcb-pg-mounting') === '1') {
			return false;
		}
		anchor.setAttribute('data-wcb-pg-mounting', '1');

		var bd = mapBarData(cfg.initialProgress);
		if (!bd) {
			fetchProgress(function (d) {
				anchor.removeAttribute('data-wcb-pg-mounting');
				if (isPremiumStackMounted()) {
					return;
				}
				var b = mapBarData(d);
				if (!b) {
					return;
				}
				var incentivesEl = document.createElement('div');
				incentivesEl.id = 'wcb-cart-page-premium-incentives';
				incentivesEl.className = 'wcb-cart-page-premium-incentives';
				incentivesEl.innerHTML = buildIncentiveStack(b);
				anchor.insertBefore(incentivesEl, layout);

				var stackEl = document.createElement('div');
				stackEl.id = 'wcb-cart-page-premium-stack';
				stackEl.className = 'wcb-cart-page-premium-stack';
				stackEl.innerHTML = buildCepBlock() + buildCouponBlock();
				placePremiumStackInSidebar(side, stackEl);
				initCep();
				initCoupon();
				runCheckoutCtaLabelSyncBurst();
			});
			return false;
		}

		var incentivesWrap = document.createElement('div');
		incentivesWrap.id = 'wcb-cart-page-premium-incentives';
		incentivesWrap.className = 'wcb-cart-page-premium-incentives';
		incentivesWrap.innerHTML = buildIncentiveStack(bd);
		anchor.insertBefore(incentivesWrap, layout);

		var stack = document.createElement('div');
		stack.id = 'wcb-cart-page-premium-stack';
		stack.className = 'wcb-cart-page-premium-stack';
		stack.innerHTML = buildCepBlock() + buildCouponBlock();
		placePremiumStackInSidebar(side, stack);

		initCep();
		initCoupon();
		anchor.removeAttribute('data-wcb-pg-mounting');

		fetchProgress(function (d) {
			var b2 = mapBarData(d);
			if (b2) {
				updateIncentiveDom(b2);
			}
			syncCheckoutCtaLabel();
		});
		runCheckoutCtaLabelSyncBurst();
		return true;
	}

	function bootCartPageExtras() {
		if (mount()) {
			return;
		}
		var done = false;
		var t;
		var obs;
		function finishBoot() {
			if (done) {
				return;
			}
			done = true;
			if (t) {
				clearInterval(t);
				t = null;
			}
			if (obs) {
				obs.disconnect();
				obs = null;
			}
		}
		var tryMount = wcbRafSchedule(function () {
			if (done) {
				return;
			}
			if (mount()) {
				finishBoot();
			}
		});
		var mountRoot =
			document.querySelector('.wp-site-blocks') ||
			document.querySelector('main') ||
			document.getElementById('content') ||
			document.body;
		obs = new MutationObserver(function () {
			tryMount();
		});
		obs.observe(mountRoot, { childList: true, subtree: true });
		var tries = 0;
		t = setInterval(function () {
			tries++;
			if (mount()) {
				finishBoot();
			} else if (tries > 50) {
				finishBoot();
			}
		}, 250);
	}

	function scheduleRefresh() {
		clearTimeout(debounceTimer);
		debounceTimer = setTimeout(function () {
			if (!document.getElementById('wcb-pg-incentive-stack')) return;
			fetchProgress(function (d) {
				var bd = mapBarData(d);
				if (bd) updateIncentiveDom(bd);
				syncCheckoutCtaLabel();
			});
		}, 400);
	}

	function bindGlobalListeners() {
		document.addEventListener('keydown', function (e) {
			if (e.key !== 'Escape') return;
			var m = document.getElementById('wcb-pg-ship-modal');
			if (m && !m.hidden) closePgModal();
		});
		document.body.addEventListener('wc-blocks_added_to_cart', scheduleRefresh);
		document.body.addEventListener('wc-blocks_added_to_cart', scheduleMergeLineTotals);
		document.body.addEventListener('wc-blocks_added_to_cart', scheduleOrderSummaryRefresh);
	}

	function observeSidebarForRefresh() {
		var onSidebarMutations = wcbRafSchedule(function () {
			var s = document.querySelector('.wc-block-cart__sidebar');
			if (s) {
				ensurePremiumStackAfterOrderSummary(s);
			}
			scheduleRefresh();
			scheduleOrderSummaryRefresh();
		});
		var side = document.querySelector('.wc-block-cart__sidebar');
		if (side && typeof MutationObserver !== 'undefined') {
			var mo = new MutationObserver(function () {
				onSidebarMutations();
			});
			mo.observe(side, { childList: true, subtree: true });
			return;
		}
		var waitRoot =
			document.querySelector('.wp-site-blocks') ||
			document.querySelector('main') ||
			document.body;
		var wait = new MutationObserver(function () {
			var s = document.querySelector('.wc-block-cart__sidebar');
			if (s) {
				wait.disconnect();
				var mo2 = new MutationObserver(function () {
					onSidebarMutations();
				});
				mo2.observe(s, { childList: true, subtree: true });
			}
		});
		wait.observe(waitRoot, { childList: true, subtree: true });
	}

	function bootCartPageExtrasPage() {
		bindGlobalListeners();
		bootCartPageExtras();
		observeSidebarForRefresh();
		mountPgOrderSummaryMirror();
		subscribeWpDataCartForSummary();
		wcbMergeCartLineTotals();
		observeCartLineTotalsMergeOnce();
		[0, 120, 350, 700, 1400].forEach(function (ms) {
			setTimeout(function () {
				wcbMergeCartLineTotals();
				observeCartLineTotalsMergeOnce();
			}, ms);
		});
		setTimeout(runCheckoutCtaLabelSyncBurst, 80);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', bootCartPageExtrasPage);
	} else {
		bootCartPageExtrasPage();
	}
})();
